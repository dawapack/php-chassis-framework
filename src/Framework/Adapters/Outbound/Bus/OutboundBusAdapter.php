<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Outbound\Bus;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Bus\OutboundBusInterface;

class OutboundBusAdapter implements OutboundBusAdapterInterface
{
    private OutboundBusInterface $outboundBus;

    /**
     * @param OutboundBusInterface $outboundBus
     */
    public function __construct(OutboundBusInterface $outboundBus)
    {
        $this->outboundBus = $outboundBus;
    }

    /**
     * @inheritDoc
     */
    public function push(
        OutboundMessageInterface $message,
        string $channel,
        string $routingKey
    ): void {
        $this->outboundBus->publish($message, $channel, $routingKey);
    }

    /**
     * @inheritDoc
     */
    public function pushResponse(
        OutboundMessageInterface $message,
        InboundMessageInterface $context
    ) {
        return $this->outboundBus->publishResponse($message, $context);
    }
}
