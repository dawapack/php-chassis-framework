<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads;

use Chassis\Application;
use Chassis\Framework\Kernel;
use Chassis\Framework\Routers\InboundRouterInterface;
use Chassis\Framework\Routers\OutboundRouterInterface;
use Chassis\Framework\Threads\Configuration\ThreadConfiguration;
use Chassis\Framework\Threads\Exceptions\ThreadInstanceException;
use parallel\Channel;
use parallel\Channel\Error\Existence;
use parallel\Future;
use parallel\Runtime;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
        $application = app();
        try {
            $runtimeBag = ThreadRuntimeBag::factory()
                ->with('basePath', $application->get('basePath'))
                ->with('threadId', $threadId)
                ->with('threadConfiguration', $this->threadConfiguration->toArray())
                ->with('workerChannel', $this->workerChannel)
                ->with('threadChannel', $this->threadChannel)
                ->with('inboundRouter', $application->get(InboundRouterInterface::class))
                ->with('outboundRouter', $application->get(OutboundRouterInterface::class));

            // Create parallel future - inject vendor autoloader
            return (new Runtime($runtimeBag->basePath . "/vendor/autoload.php"))->run(
                static function (ThreadRuntimeBag $runtimeBag): void {
                    // Define application in Closure as worker
                    define('RUNNER_TYPE', 'worker');

                    // create singleton - clone runtime bag from function argument
                    ThreadRuntimeBag::factory($runtimeBag);

                    /** @var Application $app */
                    $app = require $runtimeBag->basePath . '/bootstrap/app.php';

                    // Start processing jobs
                    (new Kernel($app))->boot();
                }, [$runtimeBag]
            );
        } catch (Throwable $reason) {
            $application->logger()->error(
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
