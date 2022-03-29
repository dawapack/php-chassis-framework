<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Outbound\Bus;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;

interface OutboundBusAdapterInterface
{
    /**
     * @param OutboundMessageInterface $message
     * @param string $channel
     * @param string $routingKey
     *
     * @return void
     */
    public function push(
        OutboundMessageInterface $message,
        string $channel,
        string $routingKey
    ): void;

    /**
     * @param OutboundMessageInterface $message
     * @param InboundMessageInterface $context
     *
     * @return null
     */
    public function pushResponse(
        OutboundMessageInterface $message,
        InboundMessageInterface $context
    );
}
