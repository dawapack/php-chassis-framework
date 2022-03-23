<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Inbound\Bus;

use Chassis\Framework\Bus\InboundBusInterface;

class InboundBusAdapter implements InboundBusAdapterInterface
{
    private InboundBusInterface $inboundBus;
    private array $options;

    /**
     * @param InboundBusInterface $inboundBus
     */
    public function __construct(InboundBusInterface $inboundBus)
    {
        $this->inboundBus = $inboundBus;
    }

    /**
     * @inheritdoc
     */
    public function setOptions(array $options): InboundBusAdapter
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function subscribe(string $channel): void
    {
        $consumeOptions = $this->options ?? [];
        $this->inboundBus->consume($channel, $consumeOptions);
    }

    /**
     * @inheritdoc
     */
    public function pool(): void
    {
        $this->inboundBus->iterate();
    }

    /**
     * @inheritdoc
     */
    public function get(string $channel, string $correlationId = null, int $timeout = null)
    {
        return $this->inboundBus->get($channel, $correlationId, $timeout);
    }
}
