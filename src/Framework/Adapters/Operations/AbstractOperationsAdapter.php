<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Operations;

use Chassis\Application;
use Chassis\Framework\Adapters\Inbound\Bus\InboundBusAdapterInterface;
use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Adapters\Outbound\Bus\OutboundBusAdapterInterface;
use Chassis\Framework\Bus\SetupBusInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use function Chassis\Helpers\app;

abstract class AbstractOperationsAdapter implements OperationsAdapterInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "operations_adapter_";

    protected string $operation;
    protected string $channelName;
    protected string $routingKey;
    protected string $replyTo;
    protected bool $isSyncOverAsync = false;
    protected int $getTimeout = 30;

    private Application $application;
    private OutboundMessageInterface $message;
    private OutboundBusAdapterInterface $outboundBusAdapter;
    private InboundBusAdapterInterface $inboundBusAdapter;
    private SetupBusInterface $bus;

    /**
     * @param OutboundBusAdapterInterface|null $outboundBusAdapter
     * @param InboundBusAdapterInterface|null $inboundBusAdapter
     * @param SetupBusInterface|null $bus
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        Application $application = null,
        OutboundBusAdapterInterface $outboundBusAdapter = null,
        InboundBusAdapterInterface $inboundBusAdapter = null,
        SetupBusInterface $bus = null
    ) {
        $this->application = is_null($application) ? app() : $application;
        $this->outboundBusAdapter = is_null($outboundBusAdapter)
            ? $this->application->get(OutboundBusAdapterInterface::class)
            : $outboundBusAdapter;
        $this->inboundBusAdapter = is_null($inboundBusAdapter)
            ? $this->application->get(InboundBusAdapterInterface::class)
            : $inboundBusAdapter;
        $this->bus = is_null($bus)
            ? $this->application->get(SetupBusInterface::class)
            : $bus;
    }

    /**
     * @param OutboundMessageInterface $message
     *
     * @return InboundMessageInterface|null
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(OutboundMessageInterface $message): ?InboundMessageInterface
    {
        try {
            $this->message = $message;
            return $this->push();
        } catch (Throwable $reason) {
            $this->application->logger()->error(
                $reason->getMessage(),
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "exception",
                    "error" => $reason
                ]
            );
        }

        return null;
    }

    /**
     * @return InboundMessageInterface|null
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function push(): ?InboundMessageInterface
    {
        if ($this->isSyncOverAsync === true) {
            $this->channelName = "";
            $this->replyTo = $this->getCallbackQueue();
        }
        $this->message->setProperty("type", $this->operation);

        $this->outboundBusAdapter->push($this->message, $this->channelName, $this->routingKey);

        if ($this->isSyncOverAsync === true) {
            return $this->inboundBusAdapter->get(
                $this->replyTo,
                $this->message->getProperty("correlation_id"),
                $this->getTimeout
            );
        }

        return null;
    }

    /**
     * @return string
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getCallbackQueue(): string
    {
        if ($this->application->has("activeRpcResponsesQueue")) {
            return $this->application->get("activeRpcResponsesQueue");
        }
        // create the queue if not exists
        $queueName = $this->bus->setupCallbackQueue();
        $this->application->add("activeRpcResponsesQueue", $queueName);

        return $queueName;
    }
}
