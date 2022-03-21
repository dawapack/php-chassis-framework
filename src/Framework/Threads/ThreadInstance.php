<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads;

use Chassis\Application;
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
use Chassis\Framework\BootstrapBag;
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
            $bootstrapBag = BootstrapBag::factory()
                ->with('basePath', app('basePath'))
                ->with('threadId', $threadId)
                ->with('threadConfiguration', $this->threadConfiguration->toArray())
                ->with('workerChannel', $this->workerChannel)
                ->with('threadChannel', $this->threadChannel)
                ->with('inboundRouter', app(InboundRouterInterface::class))
                ->with('outboundRouter', app(OutboundRouterInterface::class));

            // Create parallel future
            return (new Runtime($bootstrapBag->basePath . "/vendor/autoload.php"))->run(
                static function (BootstrapBag $bootstrapBag): void {
                    // Define application in Closure as worker
                    define('RUNNER_TYPE', 'worker');

                    // create singleton - clone function argument
                    BootstrapBag::factory($bootstrapBag);

                    /** @var Application $app */
                    $app = require $bootstrapBag->basePath . '/bootstrap/app.php';

                    // Start processing jobs
                    (new Kernel($app))->boot();
                }, [$bootstrapBag]
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
