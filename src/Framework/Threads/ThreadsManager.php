<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads;

use Chassis\Framework\InterProcessCommunication\InterProcessCommunication;
use Chassis\Framework\Threads\Configuration\ThreadConfiguration;
use Chassis\Framework\Threads\Configuration\ThreadsConfigurationInterface;
use Chassis\Framework\Threads\Exceptions\ThreadInstanceException;
use parallel\Events;
use parallel\Events\Error\Timeout;
use parallel\Events\Event;
use parallel\Events\Event\Type as EventType;
use Psr\Log\LoggerInterface;

use function Chassis\Helpers\app;

class ThreadsManager implements ThreadsManagerInterface
{
    private const LOGGER_COMPONENT_PREFIX = "thread_manager_";
    private const EVENTS_POOL_TIMEOUT_MS = 1;
    private const LOOP_EACH_MS = 100;

    private ThreadsConfigurationInterface $threadsConfiguration;
    private Events $events;
    private LoggerInterface $logger;
    private array $threads = [];

    /**
     * ThreadsManager constructor.
     *
     * @param ThreadsConfigurationInterface $threadsConfiguration
     * @param Events $events
     * @param LoggerInterface $logger
     */
    public function __construct(
        ThreadsConfigurationInterface $threadsConfiguration,
        Events $events,
        LoggerInterface $logger
    ) {
        $this->threadsConfiguration = $threadsConfiguration;
        $this->events = $events;
        $this->logger = $logger;
    }

    /**
     * @param bool $stopRequested
     *
     * @return void
     * @throws ThreadInstanceException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function start(bool &$stopRequested): void
    {
        $this->threadsSetup();
        $this->eventsSetup();

        $startAt = microtime(true);
        do {
            if ($stopRequested) {
                $this->stop();
                break;
            }
            // wait for threads event
            $this->eventsPoll();
            // Wait a while - prevent CPU load
            $this->loopWait(self::LOOP_EACH_MS, $startAt);
            $startAt = microtime(true);
        } while (true);
    }

    /**
     * @return void
     */
    protected function stop(): void
    {
        /**
         * @var ThreadInstance $threadInstance
         */
        foreach ($this->threads as $threadInstance) {
            (new InterProcessCommunication($threadInstance->getWorkerChannel(), null))
                ->setMessage("abort")
                ->send();
        }

        $startAt = microtime(true);
        do {
            // wait for threads event
            $this->eventsPoll();
            // Wait a while - prevent CPU load
            $this->loopWait(self::LOOP_EACH_MS, $startAt);
            $startAt = microtime(true);
        } while (!empty($this->threads));
    }

    /**
     * @return void
     */
    protected function eventsPoll(): void
    {
        try {
            do {
                // Poll for events from threads
                $event = $this->events->poll();
                if (is_null($event)) {
                    break;
                }
                $this->eventHandler($event);
            } while (true);
        } catch (Timeout $reason) {
            // fault-tolerant - nothing to do
        }
    }

    /**
     * @param Event $event
     *
     * @return void
     *
     * @throws ThreadInstanceException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function eventHandler(Event $event): void
    {
        if ($event->type !== EventType::Read) {
            $this->logger->warning(
                "got unhandled event",
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "event_handler",
                    "event" => (array)$event
                ]
            );
            return;
        }

        // handle event
        $threadId = str_replace(array("-worker", "-thread"), "", $event->source);

        var_dump("Thread manager polling message - " . json_encode($event->value));

        $channel = $this->threads[$threadId]->getThreadChannel();
        $ipc = (new InterProcessCommunication($channel, $event))->handle();
        if ($ipc->isRespawnRequested()) {
            $this->respawnThread($this->threads[$threadId]->getConfiguration());
        }
        if ($ipc->isAborting() || $ipc->isRespawnRequested()) {
            unset($this->threads[$threadId]);
            return;
        }
        $this->events->addChannel($channel);
    }

    /**
     * @return void
     *
     * @throws ThreadInstanceException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function threadsSetup(): void
    {
        // Spawn infrastructure thread
        $this->threadsConfiguration->hasInfrastructureThread() && $this->spawnThread(
            $this->threadsConfiguration->getThreadConfiguration('infrastructure')
        );
        // Spawn centralized configuration thread
        $this->threadsConfiguration->hasCentralizedConfigurationThread() && $this->spawnThread(
            $this->threadsConfiguration->getThreadConfiguration('configuration')
        );
        // Spawn worker threads
        $workersConfiguration = $this->threadsConfiguration->getThreadConfiguration('worker');
        if ($workersConfiguration->enabled) {
            foreach ($workersConfiguration->channels as $channel) {
                $this->spawnThread(new ThreadConfiguration($channel));
            }
        }
    }

    /**
     * @param ThreadConfiguration $threadConfiguration
     *
     * @return void
     *
     * @throws ThreadInstanceException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function spawnThread(ThreadConfiguration $threadConfiguration): void
    {
        for ($threadCnt = 0; $threadCnt < $threadConfiguration->minimum; $threadCnt++) {
            /** @var ThreadInstance $threadInstance */
            $threadInstance = app(ThreadInstanceInterface::class);
            $threadInstance->setConfiguration($threadConfiguration);

            $this->startThread($threadInstance);
        }
    }

    /**
     * @param array $configuration
     *
     * @return void
     *
     * @throws ThreadInstanceException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function respawnThread(array $configuration): void
    {
        $threadConfiguration = new ThreadConfiguration($configuration);
        /** @var ThreadInstance $threadInstance */
        $threadInstance = app(ThreadInstanceInterface::class);
        $threadInstance->setConfiguration($threadConfiguration);

        $this->startThread($threadInstance);
    }

    /**
     * @param ThreadInstance $threadInstance
     *
     * @return void
     *
     * @throws ThreadInstanceException
     */
    protected function startThread(ThreadInstance $threadInstance)
    {
        $threadId = $threadInstance->spawn();
        $this->threads[$threadId] = $threadInstance;
        // add event listener
        $this->events->addChannel($threadInstance->getThreadChannel());
    }

    /**
     * @return void
     */
    protected function eventsSetup(): void
    {
        // timeout must be in microseconds
        $this->events->setBlocking(true);
        $this->events->setTimeout(self::EVENTS_POOL_TIMEOUT_MS);
    }

    /**
     * @param int $loopEach
     * @param float $startAt
     *
     * @return void
     */
    private function loopWait(int $loopEach, float $startAt): void
    {
        $loopWait = $loopEach - (round((microtime(true) - $startAt) * 1000));
        if ($loopWait > 0) {
            usleep(((int)$loopWait * 1000));
        }
    }
}
