<?php

declare(strict_types=1);

namespace Chassis\Framework\Workers;

use Chassis\Application;
use Chassis\Framework\Adapters\Inbound\InboundBusAdapterInterface;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelIterateMaxRetryException;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelNameNotFoundException;
use Chassis\Framework\Bus\SetupBusInterface;
use Chassis\Framework\InterProcessCommunication\IPCChannelsInterface;
use Chassis\Framework\InterProcessCommunication\DataTransferObject\IPCMessage;
use Chassis\Framework\InterProcessCommunication\ParallelChannels;
use Chassis\Framework\Threads\Exceptions\ThreadConfigurationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class Worker implements WorkerInterface
{
    private const LOGGER_COMPONENT_PREFIX = "worker_";
    private const BUS_POOL_MAX_RETRY = 5;

    private Application $application;
    private IPCChannelsInterface $ipcChannels;
    private InboundBusAdapterInterface $inboundBusAdapter;
    private int $iterateRetry = 0;

    /**
     * @param Application $application
     * @param IPCChannelsInterface $ipcChannels
     * @param InboundBusAdapterInterface $inboundBusAdapter
     */
    public function __construct(
        Application $application,
        IPCChannelsInterface $ipcChannels,
        InboundBusAdapterInterface $inboundBusAdapter
    ) {
        $this->application = $application;
        $this->ipcChannels = $ipcChannels;
        $this->inboundBusAdapter = $inboundBusAdapter;
    }

    /**
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function start(): void
    {
        try {
            $this->setup();
            do {
                // IPC channel event pooling
                if (!$this->ipcPooling()) {
                    break;
                }
                // bus adapter pooling
                $this->busPooling();
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
            $this->ipcChannels->sendTo(
                $this->ipcChannels->getThreadChannel(),
                (new IPCMessage())->set(ParallelChannels::METHOD_RESPAWN_REQUESTED)
            );
        }
    }

    /**
     * @return bool
     */
    protected function ipcPooling(): bool
    {
        // channel events pool
        $this->ipcChannels->eventsPoll();
        if (!$this->ipcChannels->isAbortRequested()) {
            return true;
        }

        // send aborting message to thread manager
        $this->ipcChannels->sendTo(
            $this->ipcChannels->getThreadChannel(),
            (new IPCMessage())->set(ParallelChannels::METHOD_ABORTING)
        );

        return false;
    }

    /**
     * @return void
     *
     * @throws StreamerChannelIterateMaxRetryException
     */
    protected function busPooling(): void
    {
        try {
            $this->inboundBusAdapter->pool();
            $this->iterateRetry = 0;
        } catch (Throwable $reason) {
            // retry pattern
            $this->iterateRetry++;
            if ($this->iterateRetry >= self::BUS_POOL_MAX_RETRY) {
                throw new StreamerChannelIterateMaxRetryException("streamer channel iterate - to many retry");
            }
            // wait before retry
            sleep(1);
        }
    }

    /**
     * @return void
     *
     * @throws ThreadConfigurationException
     * @throws StreamerChannelNameNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setup(): void
    {
        $threadConfiguration = $this->application->get('threadConfiguration');
        switch ($threadConfiguration["threadType"]) {
            case "infrastructure":
                /** @var SetupBusInterface $bus */
                $bus = $this->application->get(SetupBusInterface::class);
                $bus->setup();
                break;
            case "configuration":
                break;
            case "worker":
                // wait a while - infrastructure must declare exchanges, queues & bindings
                usleep(rand(2500000, 4000000));
                $this->inboundBusAdapter->subscribe($threadConfiguration["channelName"]);
                break;
            default:
                throw new ThreadConfigurationException("unknown thread type");
        }
    }
}
