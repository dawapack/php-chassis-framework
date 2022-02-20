<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads;

use Chassis\Application;
use Chassis\Framework\InterProcessCommunication\ChannelsInterface;
use Chassis\Framework\InterProcessCommunication\ParallelChannels;
use Chassis\Framework\Kernel;
use Chassis\Framework\Routers\RouterInterface;
use Chassis\Framework\Threads\Configuration\ThreadConfiguration;
use Chassis\Framework\Threads\Exceptions\ThreadInstanceException;
use Chassis\Framework\Workers\Worker;
use Chassis\Framework\Workers\WorkerInterface;
use parallel\Channel;
use parallel\Channel\Error\Existence;
use parallel\Events;
use parallel\Future;
use parallel\Runtime;
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
     * @return Future|null
     */
    private function createFuture(string $threadId): ?Future
    {
        // Create parallel runtime - inject vendor autoload as bootstrap
        try {
            $basePath = app('basePath');
            // Create parallel future
            return (new Runtime($basePath . "/vendor/autoload.php"))->run(
                static function (
                    string $basePath,
                    string $threadId,
                    array $threadConfiguration,
                    Channel $workerChannel,
                    Channel $threadChannel,
                    RouterInterface $router
                ): void {
                    // Define application in Closure as worker
                    define('RUNNER_TYPE', 'worker');

                    /** @var Application $app */
                    $app = require $basePath . '/bootstrap/app.php';

                    // IPC setup
                    $app->add(ChannelsInterface::class, ParallelChannels::class)
                        ->addArguments([new Events(), LoggerInterface::class]);
                    /**
                     * Add channels to IPC instance
                     *
                     * @var ParallelChannels $channels
                     */
                    $channels = $app->get(ChannelsInterface::class);
                    $channels->setWorkerChannel($workerChannel, true);
                    $channels->setThreadChannel($threadChannel);

                    // Add aliases, config, ...
                    $app->add('threadConfiguration', $threadConfiguration);
                    $app->add('threadId', $threadId);
                    $app->withConfig("threads");
                    $app->withBroker(true);
                    $app->add(WorkerInterface::class, Worker::class)
                        ->addArguments([$app, ChannelsInterface::class]);
                    $app->add(RouterInterface::class, $router);

                    // Start processing jobs
                    (new Kernel($app))->boot();
                },
                [
                    $basePath,
                    $threadId,
                    $this->threadConfiguration->toArray(),
                    $this->workerChannel,
                    $this->threadChannel,
                    app(RouterInterface::class)
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
