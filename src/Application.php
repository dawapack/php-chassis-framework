<?php

declare(strict_types=1);

namespace Chassis;

use ArrayAccess;
use Chassis\Concerns\ErrorsHandler;
use Chassis\Concerns\Runner;
use Chassis\Framework\Brokers\Amqp\Configurations\BrokerConfiguration;
use Chassis\Framework\Brokers\Amqp\Configurations\BrokerConfigurationInterface;
use Chassis\Framework\Brokers\Amqp\Contracts\ContractsManager;
use Chassis\Framework\Brokers\Amqp\Contracts\ContractsManagerInterface;
use Chassis\Framework\Brokers\Amqp\Contracts\ContractsValidator;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamerInterface;
use Chassis\Framework\Configuration\Configuration;
use Chassis\Framework\InterProcessCommunication\ChannelsInterface;
use Chassis\Framework\InterProcessCommunication\ParallelChannels;
use Chassis\Framework\Logger\LoggerApplicationContext;
use Chassis\Framework\Logger\LoggerApplicationContextInterface;
use Chassis\Framework\Logger\LoggerFactory;
use Chassis\Framework\Providers\ThreadInstanceServiceProvider;
use Chassis\Framework\Providers\ThreadsManagerServiceProvider;
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

class Application extends Container implements ArrayAccess
{
    use ErrorsHandler;
    use Runner;

    private static Application $instance;
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
                ["error" => $reason]
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
                ["error" => $reason]
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
        return $this->get(LoggerInterface::class);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function withThreads(): void
    {
        // load configurations
        $this->withConfig("threads");

        // container bindings
        $this->add(ThreadsConfigurationInterface::class, ThreadsConfiguration::class)
            ->addArgument($this->get("config")->get("threads"));
        $this->add(ChannelsInterface::class, ParallelChannels::class)
            ->addArguments([new Events(), LoggerInterface::class])
            ->setShared(false);

        // service provider declarations
        $this->addServiceProvider(new ThreadsManagerServiceProvider());
        $this->addServiceProvider(new ThreadInstanceServiceProvider());
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
        // load configurations
        $this->withConfig("broker");

        // container bindings
        $this->add(BrokerConfigurationInterface::class, BrokerConfiguration::class)
            ->addArgument($this->get("config")->get("broker"));

        if ($bindDependencies) {
            $this->bindBrokerDependencies();
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->properties[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->properties[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->properties[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    /**
     * @return void
     */
    private function bindBrokerDependencies(): void
    {
        // container bindings
        $this->add(ContractsManagerInterface::class, ContractsManager::class)
            ->addArguments([BrokerConfigurationInterface::class, new ContractsValidator()]);
        $this->add('brokerStreamConnection', function ($app) {
            return new AMQPStreamConnection(
                ...array_values(
                    $app->get(ContractsManagerInterface::class)->toStreamConnectionFunctionArguments()
                )
            );
        })->addArgument($this)->setShared(false);
        $this->add(SubscriberStreamerInterface::class, SubscriberStreamer::class)
            ->addArguments([
                $this->get('brokerStreamConnection'),
                ContractsManagerInterface::class,
                LoggerInterface::class
            ])->setShared(false);
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
        ));

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
