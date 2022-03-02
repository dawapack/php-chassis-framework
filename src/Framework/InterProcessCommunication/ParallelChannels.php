<?php

declare(strict_types=1);

namespace Chassis\Framework\InterProcessCommunication;

use Chassis\Framework\InterProcessCommunication\DataTransferObject\IPCMessage;
use parallel\Channel;
use parallel\Events;
use parallel\Events\Error\Timeout;
use parallel\Events\Event;
use parallel\Events\Event\Type as EventType;
use Psr\Log\LoggerInterface;
use Throwable;

class ParallelChannels implements ChannelsInterface
{
    public const METHOD_ABORTING = 'aborting';
    public const METHOD_ABORT_REQUESTED = 'abort';
    public const METHOD_RESPAWN_REQUESTED = 'respawn';

    private const WORKER_CHANNEL_NAME = 'worker';
    private const THREAD_CHANNEL_NAME = 'thread';
    private const LOGGER_COMPONENT_PREFIX = 'parallel_channels_';

    private ?Channel $workerChannel;
    private ?Channel $threadChannel;
    private ?IPCMessage $message;
    private Events $events;
    private LoggerInterface $logger;
    private ?string $listenedChannelName;

    /**
     * @param Events $events
     * @param LoggerInterface $logger
     */
    public function __construct(
        Events $events,
        LoggerInterface $logger
    ) {
        $this->events = $events;
        $this->logger = $logger;
        $this->events->setBlocking(false);
    }

    /**
     * @inheritDoc
     */
    public function setWorkerChannel(Channel $channel, bool $attachEventListener = false): void
    {
        $this->workerChannel = $channel;
        $attachEventListener && $this->attachEventListener($channel, self::WORKER_CHANNEL_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setThreadChannel(Channel $channel, bool $attachEventListener = false): void
    {
        $this->threadChannel = $channel;
        $attachEventListener && $this->attachEventListener($channel, self::THREAD_CHANNEL_NAME);
    }

    /**
     * @inheritDoc
     */
    public function getWorkerChannel(): ?Channel
    {
        return $this->workerChannel ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getThreadChannel(): ?Channel
    {
        return $this->threadChannel ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): ?IPCMessage
    {
        return $this->message ?? null;
    }

    /**
     * @return bool
     */
    public function isAborting(): bool
    {
        if (!is_null($this->getMessage())) {
            return $this->message->getHeader("method") === self::METHOD_ABORTING;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isAbortRequested(): bool
    {
        if (!is_null($this->getMessage())) {
            return $this->message->getHeader("method") === self::METHOD_ABORT_REQUESTED;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isRespawnRequested(): bool
    {
        if (!is_null($this->getMessage())) {
            return $this->message->getHeader("method") === self::METHOD_RESPAWN_REQUESTED;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function destroy(): void
    {
        if (isset($this->workerChannel) && ($this->workerChannel instanceof Channel)) {
            $this->workerChannel->close();
            unset($this->workerChannel);
        }

        if (isset($this->threadChannel) && ($this->threadChannel instanceof Channel)) {
            $this->threadChannel->close();
            unset($this->threadChannel);
        }
    }

    /**
     * @inheritDoc
     */
    public function sendTo(Channel $channel, IPCMessage $message): ChannelsInterface
    {
        $channel->send($message->toArray());

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function eventsPoll(): void
    {
        try {
            $event = $this->events->poll();
            if (is_null($event)) {
                return;
            }
            $this->handleEvent($event);
        } catch (Timeout $reason) {
            // fault-tolerant - timeout is a normal behaviour
        }
    }

    /**
     * @param Event $event
     *
     * @return void
     */
    private function handleEvent(Event $event): void
    {
        // handle only read event types
        if ($event->type !== EventType::Read) {
            // log other event types
            $this->logger->warning(
                "got unhandled event",
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "handle_event",
                    "event" => (array)$event
                ]
            );
            return;
        }
        // clear previous message
        unset($this->message);
        // save event value as message
        $this->message = new IPCMessage($event->value);
        $this->events->addChannel($this->getListenedChannel());
    }

    /**
     * @param Channel $channel
     *
     * @return void
     */
    private function attachEventListener(Channel $channel, string $channelName): void
    {
        $this->events->addChannel($channel);
        $this->listenedChannelName = $channelName;
    }

    /**
     * @return Channel|null
     */
    private function getListenedChannel(): ?Channel
    {
        if ($this->listenedChannelName == self::WORKER_CHANNEL_NAME) {
            return $this->getWorkerChannel();
        } elseif ($this->listenedChannelName == self::THREAD_CHANNEL_NAME) {
            return $this->getThreadChannel();
        }

        return null;
    }
}
