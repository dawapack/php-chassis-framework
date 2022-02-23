<?php

declare(strict_types=1);

namespace Chassis\Framework\Workers;

use Chassis\Application;
use Chassis\Framework\Brokers\Amqp\Streamers\InfrastructureStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamer;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelIterateMaxRetryException;
use Chassis\Framework\InterProcessCommunication\ChannelsInterface;
use Chassis\Framework\InterProcessCommunication\DataTransferObject\IPCMessage;
use Chassis\Framework\InterProcessCommunication\ParallelChannels;
use Chassis\Framework\Threads\Exceptions\ThreadConfigurationException;
use Throwable;

use function Chassis\Helpers\subscribe;

class Worker implements WorkerInterface
{
    private const LOGGER_COMPONENT_PREFIX = "worker_";
    private const SUBSCRIBER_ITERATE_MAX_RETRY = 100;

    private Application $application;
    private ChannelsInterface $channels;
    private SubscriberStreamer $subscriberStreamer;
    private int $iterateRetry = 0;

    /**
     * @param Application $application
     * @param ChannelsInterface $channels
     */
    public function __construct(
        Application $application,
        ChannelsInterface $channels
    ) {
        $this->application = $application;
        $this->channels = $channels;
    }

    /**
     * @return void
     */
    public function start(): void
    {
        try {
            $this->subscriberSetup();
            do {
                // IPC channel event poll
                if (!$this->polling()) {
                    break;
                }
                // subscriber streamer iterate
                $this->subscriberIterate();
            } while (true);
        } catch (Throwable $reason) {
            // log this error & request respawning
            $this->application->logger()->error(
                $reason->getMessage(),
                [
                    'component' => self::LOGGER_COMPONENT_PREFIX . "exception",
                    'error' => $reason
                ]
            );
            $this->channels->sendTo(
                $this->channels->getThreadChannel(),
                (new IPCMessage())->set(ParallelChannels::METHOD_RESPAWN_REQUESTED)
            );
        }

        // Close subscriber streamer channel
        if (isset($this->subscriberStreamer)) {
            $this->subscriberStreamer->closeChannel();
        }
    }

    /**
     * @return bool
     */
    protected function polling(): bool
    {
        // channel events pool
        $this->channels->eventsPoll();
        if (!$this->channels->isAbortRequested()) {
            return true;
        }

        // send aborting message to thread manager
        $this->channels->sendTo(
            $this->channels->getThreadChannel(),
            (new IPCMessage())->set(ParallelChannels::METHOD_ABORTING)
        );

        return false;
    }

    /**
     * @return void
     * @throws StreamerChannelIterateMaxRetryException
     */
    protected function subscriberIterate(): void
    {
        try {
            if (isset($this->subscriberStreamer)) {
                $this->subscriberStreamer->iterate();
                $this->iterateRetry = 0;
                return;
            }
            // need to wait here - prevent CPU load
            usleep(100000);
        } catch (Throwable $reason) {
            // retry pattern
            $this->iterateRetry++;
            if ($this->iterateRetry >= self::SUBSCRIBER_ITERATE_MAX_RETRY) {
                throw new StreamerChannelIterateMaxRetryException("streamer channel iterate - to many retry");
            }
        }
    }

    /**
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function subscriberSetup(): void
    {
        $threadConfiguration = $this->application->get('threadConfiguration');
        switch ($threadConfiguration["threadType"]) {
            case "infrastructure":
                // Broker channels setup
                (new InfrastructureStreamer($this->application))->brokerChannelsSetup();
                break;
            case "configuration":
                // TODO: implement configuration listener - (centralized configuration server feature)
                break;
            case "worker":
                // wait a while - infrastructure must declare exchanges, queues & bindings
                usleep(rand(2500000, 4000000));
                // create subscriber
                $this->subscriberStreamer = subscribe(
                    $threadConfiguration["channelName"],
                    $threadConfiguration["handler"]
                );
                break;
            default:
                throw new ThreadConfigurationException("unknown thread type");
        }
    }
}
