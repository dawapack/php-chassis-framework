<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads;

use Chassis\Application;
use Chassis\Framework\Adapters\Inbound\InboundBusAdapter;
use Chassis\Framework\Adapters\Inbound\InboundBusAdapterInterface;
use Chassis\Framework\Adapters\Outbound\OutboundBusAdapter;
use Chassis\Framework\Adapters\Outbound\OutboundBusAdapterInterface;
use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\AsyncContractInterface;
use Chassis\Framework\AsyncApi\ContractParser;
use Chassis\Framework\AsyncApi\ContractValidator;
use Chassis\Framework\AsyncApi\Transformers\AMQPTransformer;
use Chassis\Framework\AsyncApi\TransformersInterface;
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
use Chassis\Framework\InterProcessCommunication\IPCChannelsInterface;
use Chassis\Framework\InterProcessCommunication\ParallelChannels;
use Chassis\Framework\Kernel;
use Chassis\Framework\OutboundAdapters\Cache\CacheFactory;
use Chassis\Framework\OutboundAdapters\Cache\CacheFactoryInterface;
use Chassis\Framework\Routers\InboundRouterInterface;
use Chassis\Framework\Routers\OutboundRouterInterface;
use Chassis\Framework\Threads\Configuration\ThreadConfiguration;
use Chassis\Framework\Threads\Exceptions\ThreadInstanceException;
use Chassis\Framework\Workers\Worker;
use Chassis\Framework\Workers\WorkerInterface;
use Opis\JsonSchema\Validator;
use parallel\Channel;
use parallel\Channel\Error\Existence;
use parallel\Events;
use parallel\Future;
use parallel\Runtime;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

use function Chassis\Helpers\app;

class ThreadInstance implements ThreadInstanceInterface
{
    private const LOGGER_COMPONENT_PREFIX = "thread_instance_";

    private ?Future $future;
    private ?Channel $threadChannel;
    private ?Channel $workerChannel;
    private ThreadConfiguration $threadConfiguration;

    public function __destruct()
    {
        if (isset($this->workerChannel)) {
            $this->workerChannel->close();
        }
        if (isset($this->threadChannel)) {
            $this->threadChannel->close();
        }
    }

    /**
     * @inheritDoc
     */
    public function setConfiguration(ThreadConfiguration $threadConfiguration): void
    {
        $this->threadConfiguration = $threadConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration(?string $key = null)
    {
        return !is_null($key)
            ? $this->threadConfiguration->{$key} ?? null
            : $this->threadConfiguration->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getFuture(): Future
    {
        return $this->future;
    }

    /**
     * @inheritDoc
     */
    public function getThreadChannel(): Channel
    {
        return $this->threadChannel;
    }

    /**
     * @inheritDoc
     */
    public function getWorkerChannel(): Channel
    {
        return $this->workerChannel;
    }

    /**
     * @return string
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ThreadInstanceException
     */
    public function spawn(): string
    {
        $threadId = (Uuid::uuid4())->toString();

        // Create thread & worker channels
        $this->workerChannel = $this->createChannel($threadId . "-worker", Channel::Infinite);
        $this->threadChannel = $this->createChannel($threadId . "-thread", Channel::Infinite);
        if (is_null($this->workerChannel) || is_null($this->threadChannel)) {
            throw new ThreadInstanceException("creating channels for thread instance fail");
        }

        // Create future
        $this->future = $this->createFuture($threadId);

        var_dump($this->future);

        if (is_null($this->future)) {
            $this->workerChannel->close();
            $this->threadChannel->close();
            throw new ThreadInstanceException("creating future for thread instance fail");
        }

        return $threadId;
    }

    /**
     * @param string $name
     * @param int $capacity
     *
     * @return Channel|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createChannel(string $name, int $capacity): ?Channel
    {
        try {
            return (Channel::make($name, $capacity))::open($name);
        } catch (Existence $reason) {
            app()->logger()->error(
                $reason->getMessage(),
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "create_channel_exception",
                    "error" => $reason
                ]
            );
        }

        return null;
    }

    /**
     * @param string $threadId
     *
     * @return Future|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createFuture(string $threadId): ?Future
    {
        // Create parallel runtime - inject vendor autoload as bootstrap
        try {

            var_dump([
                __METHOD__,
                app()->has(InboundRouterInterface::class),
                app()->has(OutboundRouterInterface::class)
            ]);

            $basePath = app('basePath');
            // Create parallel future
            return (new Runtime($basePath . "/vendor/autoload.php"))->run(
                static function (
                    string $basePath,
                    string $threadId,
                    array $threadConfiguration,
                    Channel $workerChannel,
                    Channel $threadChannel
//                    $inboundRouter
//                    $outboundRouter
                ): void {
                    // Define application in Closure as worker
                    define('RUNNER_TYPE', 'worker');

                    do {
                        var_dump([__METHOD__, RUNNER_TYPE]);
                        sleep(30);
                    } while (true);
//                    /** @var Application $app */
//                    $app = require $basePath . '/bootstrap/app.php';
//
//                    // IPC setup
//                    $app->add(IPCChannelsInterface::class, ParallelChannels::class)
//                        ->addArguments([new Events(), LoggerInterface::class]);
//                    /**
//                     * Add channels to IPC instance
//                     *
//                     * @var ParallelChannels $channels
//                     */
//                    $channels = $app->get(IPCChannelsInterface::class);
//                    $channels->setWorkerChannel($workerChannel, true);
//                    $channels->setThreadChannel($threadChannel);
//
//                    $app->logger()->info(
//                        "debug_worker",
//                        ["component" => "debug", "thread_config" => $threadConfiguration]
//                    );
//
//                    // aliases, config, ...
//                    $app->add('threadConfiguration', $threadConfiguration);
//                    $app->add('threadId', $threadId);
//                    $app->withConfig("threads");
//                    $app->withConfig("broker");
//                    $app->withConfig("cache");
//                    $app->add(WorkerInterface::class, Worker::class)
//                        ->addArguments([$app, IPCChannelsInterface::class]);
//
//                    // general adapters
//                    $app->add(AMQPConnectorInterface::class, AMQPConnector::class);
//                    $app->add(TransformersInterface::class, AMQPTransformer::class);
//                    $app->add(MessageBusInterface::class, AMQPMessageBus::class);
//                    $app->add(InboundBusInterface::class, AMQPInboundBus::class);
//                    $app->add(OutboundBusInterface::class, AMQPOutboundBus::class);
//                    $app->add(SetupBusInterface::class, AMQPSetup::class);
//                    $app->add(AsyncContractInterface::class, function ($configuration, $transformer) {
//                        return (new AsyncContract(
//                            new ContractParser(),
//                            new ContractValidator(
//                                new Validator()
//                            )
//                        ))->setConfiguration($configuration)
//                            ->pushTransformer($transformer);
//                    })->addArguments([$app->get('config')->get('broker'), TransformersInterface::class]);
//
//                    // inbound adapters
//                    $app->add(InboundBusAdapterInterface::class, InboundBusAdapter::class);
//                    $app->add(InboundRouterInterface::class, $inboundRouter);
//
//                    // outbound adapters
//                    $app->add(OutboundBusAdapterInterface::class, OutboundBusAdapter::class);
//                    $app->add(OutboundRouterInterface::class, $outboundRouter);
//                    $app->add(CacheFactoryInterface::class, function ($configuration) {
//                        return (new CacheFactory($configuration))->build();
//                    })->addArgument($app->get('config')->get('cache'));
//
//                    // Start processing jobs
//                    (new Kernel($app))->boot();
                },
                [
                    $basePath,
                    $threadId,
                    $this->threadConfiguration->toArray(),
                    $this->workerChannel,
                    $this->threadChannel,
//                    app(InboundRouterInterface::class),
//                    app(OutboundRouterInterface::class)
                ]
            );
        } catch (Throwable $reason) {
            app()->logger()->error(
                $reason->getMessage(),
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "create_future_exception",
                    "error" => $reason
                ]
            );
        }

        return null;
    }
}
