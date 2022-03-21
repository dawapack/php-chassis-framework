<?php

declare(strict_types=1);

namespace Chassis;

use Chassis\Concerns\ErrorsHandler;
use Chassis\Concerns\Runner;
use Chassis\Framework\Adapters\Inbound\InboundBusAdapter;
use Chassis\Framework\Adapters\Inbound\InboundBusAdapterInterface;
use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Adapters\Outbound\OutboundBusAdapter;
use Chassis\Framework\Adapters\Outbound\OutboundBusAdapterInterface;
use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\AsyncContractInterface;
use Chassis\Framework\AsyncApi\ContractParser;
use Chassis\Framework\AsyncApi\ContractValidator;
use Chassis\Framework\AsyncApi\Transformers\AMQPTransformer;
use Chassis\Framework\AsyncApi\TransformersInterface;
use Chassis\Framework\RuntimeBag;
use Chassis\Framework\Brokers\Amqp\Configurations\BrokerConfiguration;
use Chassis\Framework\Brokers\Amqp\Configurations\BrokerConfigurationInterface;
use Chassis\Framework\Brokers\Amqp\Contracts\ContractsManager;
use Chassis\Framework\Brokers\Amqp\Contracts\ContractsManagerInterface;
use Chassis\Framework\Brokers\Amqp\Contracts\ContractsValidator;
use Chassis\Framework\Brokers\Amqp\Handlers\MessageHandler;
use Chassis\Framework\Brokers\Amqp\Handlers\MessageHandlerInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamerInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamerInterface;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnector;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnectorInterface;
use Chassis\Framework\Bus\AMQP\Inbound\AMQPInboundBus;
use Chassis\Framework\Bus\AMQP\Message\AMQPMessageBus;
use Chassis\Framework\Bus\AMQP\Outbound\AMQPOutboundBus;
use Chassis\Framework\Bus\AMQP\Setup\AMQPSetup;
use Chassis\Framework\Bus\InboundBusInterface;
use Chassis\Framework\Bus\MessageBusInterface;
use Chassis\Framework\Bus\OutboundBusInterface;
use Chassis\Framework\Bus\SetupBusInterface;
use Chassis\Framework\Configuration\Configuration;
use Chassis\Framework\InterProcessCommunication\IPCChannelsInterface;
use Chassis\Framework\InterProcessCommunication\ParallelChannels;
use Chassis\Framework\Logger\LoggerApplicationContext;
use Chassis\Framework\Logger\LoggerApplicationContextInterface;
use Chassis\Framework\Logger\LoggerFactory;
use Chassis\Framework\OutboundAdapters\Cache\CacheFactory;
use Chassis\Framework\OutboundAdapters\Cache\CacheFactoryInterface;
use Chassis\Framework\Providers\ThreadsServiceProvider;
use Chassis\Framework\Routers\InboundRouterInterface;
use Chassis\Framework\Routers\OutboundRouterInterface;
use Chassis\Framework\Threads\Configuration\ThreadsConfiguration;
use Chassis\Framework\Threads\Configuration\ThreadsConfigurationInterface;
use Chassis\Framework\Workers\Worker;
use Chassis\Framework\Workers\WorkerInterface;
use League\Config\Configuration as LeagueConfiguration;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Opis\JsonSchema\Validator;
use parallel\Events;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Application extends Container
{
    use ErrorsHandler;
    use Runner;

    private static Application $instance;
    private LoggerInterface $logger;
    private string $basePath;
    private array $properties;

    /**
     * Application constructor.
     *
     * @param string|null $basePath
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function __construct(string $basePath = null)
    {
        parent::__construct();
        $this->basePath = $basePath;

        $this->defaultToShared(true);
        $this->enableAutoWiring();
        $this->bootstrapContainer();
        $this->registerErrorHandling();
        $this->registerRunnerType();

        if (!$this->runningInConsole()) {
            trigger_error("Run only in cli mode", E_USER_ERROR);
        }

        // final bootstraps
        if ($this->isDaemon()) {
            $this->bootstrapDaemon();
        } elseif ($this->isWorker()) {
            $this->bootstrapWorker();
        }

        self::$instance = $this;
    }

    /**
     * @return Application|null
     */
    public static function getInstance(): ?Application
    {
        return isset(self::$instance) && (self::$instance instanceof Application)
            ? self::$instance
            : null;
    }

    /**
     * @return bool
     */
    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * @param string $key
     *
     * @return array|mixed|null
     *
     * @throws Throwable
     */
    public function config(string $key)
    {
        try {
            return $this->get('config')->get($key);
        } catch (Throwable $reason) {
            $this->logger()->error(
                $reason->getMessage(),
                [
                    "component" => "application_configuration_get_exception",
                    "error" => $reason
                ]
            );
        }
        return null;
    }

    /**
     * @param string $alias
     *
     * @return void
     *
     * @throws Throwable
     */
    public function withConfig(string $alias): void
    {
        try {
            $this->get('config')->load($alias);
        } catch (Throwable $reason) {
            $this->logger()->error(
                $reason->getMessage(),
                [
                    "component" => "application_with_configuration_exception",
                    "error" => $reason
                ]
            );
        }
    }

    /**
     * @return LoggerInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function logger(): LoggerInterface
    {
        if (isset($this->logger)) {
            return $this->logger;
        }
        $this->logger = $this->get(LoggerInterface::class);
        return $this->logger;
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function withThreads(): void
    {
        $this->withConfig("threads");

        $this->add(ThreadsConfigurationInterface::class, ThreadsConfiguration::class)
            ->addArgument($this->get("config")->get("threads"));

        $this->add(IPCChannelsInterface::class, ParallelChannels::class)
            ->addArguments([new Events(), LoggerInterface::class])
            ->setShared(false);

        $this->addServiceProvider(new ThreadsServiceProvider());
    }

    /**
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function bootstrapContainer(): void
    {
        // Add aliases
        $this->add(LoggerApplicationContextInterface::class, LoggerApplicationContext::class);
        $this->add(LoggerInterface::class, (new LoggerFactory($this->basePath))($this));
        $this->add('config', new Configuration(
            new LeagueConfiguration(),
            $this->logger(),
            $this->basePath,
            ['app']
        ));

        // Add paths
        $this->add('basePath', $this->basePath);
        $this->add('configPath', $this->basePath . "/config");
        $this->add('logsPath', $this->basePath . "/logs");
        $this->add('tempPath', $this->basePath . "/tmp");
        $this->add('vendorPath', $this->basePath . "/vendor");
    }

    /**
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    private function bootstrapDaemon(): void
    {
        $this->withConfig("threads");

        $this->add(ThreadsConfigurationInterface::class, ThreadsConfiguration::class)
            ->addArgument($this->get("config")->get("threads"));

        $this->add(IPCChannelsInterface::class, ParallelChannels::class)
            ->addArguments([new Events(), LoggerInterface::class])
            ->setShared(false);

        $this->addServiceProvider(new ThreadsServiceProvider());
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Throwable
     */
    private function bootstrapWorker(): void
    {
        // get instance of bootstrap bag
        $runtimeBag = RuntimeBag::factory();

        // IPC setup
        $this->add(IPCChannelsInterface::class, ParallelChannels::class)
            ->addArguments([new Events(), LoggerInterface::class]);

        /**
         * Add channels to IPC instance
         *
         * @var ParallelChannels $channels
         */
        $channels = $this->get(IPCChannelsInterface::class);
        $channels->setWorkerChannel($runtimeBag->workerChannel, true);
        $channels->setThreadChannel($runtimeBag->threadChannel);

        // aliases, config, ...
        $this->add('threadConfiguration', $runtimeBag->threadConfiguration);
        $this->add('threadId', $runtimeBag->threadId);
        $this->withConfig("threads");
        $this->withConfig("broker");
        $this->withConfig("cache");
        $this->add(WorkerInterface::class, Worker::class)
            ->addArguments([
                $this,
                IPCChannelsInterface::class,
                InboundBusAdapterInterface::class,
                SetupBusInterface::class
            ]);

        // general adapters
        $this->add(MessageBusInterface::class, AMQPMessageBus::class);
        $this->add(TransformersInterface::class, AMQPTransformer::class);
        $this->add(InboundMessageInterface::class, InboundMessage::class)
            ->addArgument(MessageBusInterface::class);
        $this->add(OutboundMessageInterface::class, OutboundMessage::class)
            ->addArgument(MessageBusInterface::class);
        $this->add(AMQPConnectorInterface::class, AMQPConnector::class)
            ->addArgument(AsyncContractInterface::class);
        $this->add(InboundBusInterface::class, AMQPInboundBus::class)
            ->addArguments([
                AMQPConnectorInterface::class,
                AsyncContractInterface::class,
                InboundMessageInterface::class,
                InboundRouterInterface::class,
                LoggerInterface::class
            ]);
        $this->add(OutboundBusInterface::class, AMQPOutboundBus::class)
            ->addArguments([
                AMQPConnectorInterface::class,
                AsyncContractInterface::class,
                LoggerInterface::class
            ]);
        $this->add(SetupBusInterface::class, AMQPSetup::class)
            ->addArguments([
                AMQPConnectorInterface::class,
                AsyncContractInterface::class,
                LoggerInterface::class
            ]);
        $this->add(AsyncContractInterface::class, function ($configuration, $transformer) {
            return (new AsyncContract(
                new ContractParser(),
                new ContractValidator(
                    new Validator()
                )
            ))->setConfiguration($configuration)
                ->pushTransformer($transformer);
        })->addArguments([$this->get('config')->get('broker'), TransformersInterface::class]);

        // inbound adapters
        $this->add(InboundRouterInterface::class, $runtimeBag->inboundRouter);
        $this->add(InboundBusAdapterInterface::class, InboundBusAdapter::class)
            ->addArgument(InboundBusInterface::class);

        // outbound adapters
        $this->add(OutboundRouterInterface::class, $runtimeBag->outboundRouter);
        $this->add(OutboundBusAdapterInterface::class, OutboundBusAdapter::class)
            ->addArgument(OutboundBusInterface::class);
        $this->add(CacheFactoryInterface::class, function ($configuration) {
            return (new CacheFactory($configuration))->build();
        })->addArgument($this->get('config')->get('cache'));
    }

    /**
     * Instantiate the container auto wiring - resolutions will be cached
     *
     * @return void
     */
    private function enableAutoWiring(): void
    {
        $this->delegate(new ReflectionContainer(true));
    }
}
