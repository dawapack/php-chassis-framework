<?php

declare(strict_types=1);

namespace Chassis;

use Chassis\Concerns\ErrorsHandler;
use Chassis\Concerns\Runner;
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
use Chassis\Framework\Configuration\Configuration;
use Chassis\Framework\InterProcessCommunication\ChannelsInterface;
use Chassis\Framework\InterProcessCommunication\ParallelChannels;
use Chassis\Framework\Logger\LoggerApplicationContext;
use Chassis\Framework\Logger\LoggerApplicationContextInterface;
use Chassis\Framework\Logger\LoggerFactory;
use Chassis\Framework\Providers\ThreadsServiceProvider;
use Chassis\Framework\Threads\Configuration\ThreadsConfiguration;
use Chassis\Framework\Threads\Configuration\ThreadsConfigurationInterface;
use League\Config\Configuration as LeagueConfiguration;
use League\Container\Container;
use League\Container\ReflectionContainer;
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

        pcntl_async_signals(true);

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

        $this->add(ChannelsInterface::class, ParallelChannels::class)
            ->addArguments([new Events(), LoggerInterface::class])
            ->setShared(false);

        $this->addServiceProvider(new ThreadsServiceProvider());
    }

    /**
     * @param bool $bindDependencies
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function withBroker(bool $bindDependencies = false): void
    {
        $this->withConfig("broker");

        $this->add(BrokerConfigurationInterface::class, BrokerConfiguration::class)
            ->addArgument($this->get("config")->get("broker"));

        $bindDependencies && $this->bindBrokerDependencies();
    }

    /**
     * @return void
     */
    private function bindBrokerDependencies(): void
    {
        $this->add(ContractsManagerInterface::class, ContractsManager::class)
            ->addArguments([BrokerConfigurationInterface::class, new ContractsValidator()]);

        $this->add('brokerStreamConnection', function ($contractsManager) {
            return new AMQPStreamConnection(...$contractsManager->toStreamConnectionFunctionArguments());
        })->addArgument(ContractsManagerInterface::class);

        $this->add(MessageHandlerInterface::class, MessageHandler::class);

        $this->add(SubscriberStreamerInterface::class, SubscriberStreamer::class)
            ->addArgument($this)->setShared(false);

        $this->add(PublisherStreamerInterface::class, PublisherStreamer::class)
            ->addArgument($this)->setShared(false);
    }

    /**
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function bootstrapContainer(): void
    {
        // Add singletons
        $this->add(LoggerApplicationContextInterface::class, LoggerApplicationContext::class);
        $this->add(LoggerInterface::class, (new LoggerFactory($this->basePath))($this));
        $this->add('config', new Configuration(
            new LeagueConfiguration(),
            $this->logger(),
            $this->basePath,
            ['app']
        ))->setShared(false);

        // Add paths
        $this->add('basePath', $this->basePath);
        $this->add('configPath', $this->basePath . "/config");
        $this->add('logsPath', $this->basePath . "/logs");
        $this->add('tempPath', $this->basePath . "/tmp");
        $this->add('vendorPath', $this->basePath . "/vendor");
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
