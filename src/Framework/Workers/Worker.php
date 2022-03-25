<?php

declare(strict_types=1);

namespace Chassis\Framework\Workers;

use Chassis\Application;
use Chassis\Framework\Adapters\Inbound\Bus\InboundBusAdapterInterface;
use Chassis\Framework\Bus\Exceptions\ChannelBusException;
use Chassis\Framework\Bus\SetupBusInterface;
use Chassis\Framework\Logger\Logger;
use Chassis\Framework\Threads\DataTransferObject\IPCMessage;
use Chassis\Framework\Threads\Exceptions\ThreadConfigurationException;
use Chassis\Framework\Threads\InterProcessCommunication\IPCChannelsInterface;
use Chassis\Framework\Threads\InterProcessCommunication\ParallelChannels;
use DateTime;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class Worker implements WorkerInterface
{
    private const LOGGER_COMPONENT_PREFIX = "worker_";
    private const BUS_POOL_MAX_RETRY = 5;
    private const DEFAULT_DATETIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    private Application $application;
    private IPCChannelsInterface $ipcChannels;
    private InboundBusAdapterInterface $inboundBusAdapter;
    private int $iterateRetry = 0;
    private int $jobsBeforeRespawn = 0;
    private int $processUntil = 0;
    private bool $hasLimits = false;

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
     * @throws NotFoundExceptionInterface
     */
    public function start(): void
    {
        try {
            $this->setup();
            do {
                if (!$this->ipcPooling()) {
                    break;
                }
                $this->busPooling();
            } while (!$this->limitReached());
        } catch (Throwable $reason) {
            $this->application->logger()->error(
                $reason->getMessage(),
                [
                    'component' => self::LOGGER_COMPONENT_PREFIX . "exception",
                    'error' => $reason
                ]
            );
        }

        if (isset($reason) || $this->limitReached()) {
            // send respawn requested message to thread manager process
            $this->sendIpcMessage(ParallelChannels::METHOD_RESPAWN_REQUESTED);
        }
    }

    /**
     * @return bool
     */
    protected function ipcPooling(): bool
    {
        // channel events pool
        $this->ipcChannels->eventsPoll();

        if ($this->ipcChannels->isAbortRequested()) {
            // send aborting message to thread manager process
            $this->sendIpcMessage(ParallelChannels::METHOD_ABORTING);
            return false;
        }

        $ipcMessage = $this->ipcChannels->getMessage();
        if ($ipcMessage instanceof IPCMessage) {
            $this->handleIPCMessage($ipcMessage);
        }

        return true;
    }

    /**
     * @return void
     *
     * @throws ChannelBusException
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
                throw new ChannelBusException("channel iterate - to many retry");
            }
            // wait before retry
            sleep(1);
        }
    }

    /**
     * @return void
     *
     * @throws ThreadConfigurationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setup(): void
    {
        $threadConfiguration = $this->application->get('threadConfiguration');

        // setup limits
        $this->setWorkerLimits($threadConfiguration["ttl"], $threadConfiguration["maxJobs"]);

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
                // this thread type has limits
                $this->hasLimits = true;
                break;
            default:
                throw new ThreadConfigurationException("unknown thread type");
        }
    }

    /**
     * @param string $method
     * @param string|array|null $body
     * @param array $headers
     *
     * @return void
     */
    protected function sendIpcMessage(string $method, $body = null, array $headers = []): void
    {
        $this->ipcChannels->sendTo(
            $this->ipcChannels->getThreadChannel(),
            (new IPCMessage())->set($method, $body, $headers)
        );
    }

    /**
     * @param IPCMessage $ipcMessage
     *
     * @return void
     */
    protected function handleIPCMessage(IPCMessage $ipcMessage): void
    {
        if ($ipcMessage->getHeader("method") === ParallelChannels::METHOD_JOB_PROCESSED) {
            $this->jobsBeforeRespawn--;
        }
    }

    /**
     * @param int $ttl
     * @param int $maxJobs
     *
     * @return void
     *
     * @throws Exception
     * @throws ContainerExceptionInterface
     */
    protected function setWorkerLimits(int $ttl, int $maxJobs): void
    {
        // avoid restarting workers at the same time
        $tenPercentOfTimeToLive = (int)($ttl * 0.1);
        // no less than 60
        if ($tenPercentOfTimeToLive < 60) {
            $tenPercentOfTimeToLive = 60;
        }
        $randomized = (double)(rand(0,$tenPercentOfTimeToLive) - (int)($tenPercentOfTimeToLive / 2));
        $this->processUntil = (int)($ttl + time() + $randomized);

        $this->jobsBeforeRespawn = $maxJobs;

        $this->application->logger()->info(
            "worker limits info",
            [
                'component' => self::LOGGER_COMPONENT_PREFIX . "limits_info",
                'limits' => [
                    'until' => (new DateTime($this->processUntil))->format(self::DEFAULT_DATETIME_FORMAT),
                    'maxJobs' => $this->jobsBeforeRespawn
                ]
            ]
        );
    }

    /**
     * @return bool
     */
    protected function limitReached(): bool
    {
        return $this->hasLimits && ($this->processUntil < time() || $this->jobsBeforeRespawn <= 0);
    }
}
