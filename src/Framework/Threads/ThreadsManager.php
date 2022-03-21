<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads;

use Chassis\Framework\InterProcessCommunication\DataTransferObject\IPCMessage;
use Chassis\Framework\InterProcessCommunication\ParallelChannels;
use Chassis\Framework\Threads\Configuration\ThreadConfiguration;
use Chassis\Framework\Threads\Configuration\ThreadsConfigurationInterface;
use Chassis\Framework\Threads\Exceptions\ThreadInstanceException;
use parallel\Events;
use parallel\Events\Error\Timeout;
use parallel\Events\Event;
use parallel\Events\Event\Type as EventType;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

use function Chassis\Helpers\app;

class ThreadsManager implements ThreadsManagerInterface
{
    private const LOGGER_COMPONENT_PREFIX = "thread_manager_";

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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function start(bool &$stopRequested): void
    {
        $this->threadsSetup();
        $this->events->setBlocking(false);
        do {
            if ($stopRequested) {
                $this->stop();
                break;
            }
            // wait for threads event
            $this->eventsPoll();
            // wait a while, prevent CPU load
            usleep(330000);
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
            ($threadInstance->getWorkerChannel())
                ->send(
                    (new IPCMessage())
                        ->set(ParallelChannels::METHOD_ABORT_REQUESTED)
                        ->toArray()
                );
        }

        do {
            // wait for threads event
            $this->eventsPoll();
            // wait a while, prevent CPU load
            usleep(10000);
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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

        // get thread id from event
        $threadId = $this->getThreadIdFromEventSource($event);
        if (is_null($threadId)) {
            return;
        }
        $messageMethod = (new IPCMessage($event->value))->getHeader("method");

        // is respawn requested?
        if ($messageMethod === ParallelChannels::METHOD_RESPAWN_REQUESTED) {
            $this->respawnThread($this->threads[$threadId]->getConfiguration());
        }

        // finally, on aborting or respawn, remove thread from the list
        if (
            $messageMethod === ParallelChannels::METHOD_ABORTING
            || $messageMethod === ParallelChannels::METHOD_RESPAWN_REQUESTED
        ) {
            unset($this->threads[$threadId]);
            return;
        }
        $this->events->addChannel(
            $this->threads[$threadId]->getThreadChannel()
        );
    }

    /**
     * @return void
     *
     * @throws ThreadInstanceException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
                if (!$channel->enabled) {
                    continue;
                }
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * @param Event $event
     *
     * @return string|null
     */
    protected function getThreadIdFromEventSource(Event $event): ?string
    {
        $threadId = str_replace(
            array("-worker", "-thread"),
            "",
            $event->source
        );
        return isset($this->threads[$threadId]) ? $threadId : null;
    }
}
