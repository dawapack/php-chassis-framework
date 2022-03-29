<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;

interface OutboundBusInterface
{
    /**
     * @param OutboundMessageInterface $message
     * @param string $channel
     * @param string $routingKey
     * @param int $timeout
     *
     * @return void
     */
    public function publish(
        OutboundMessageInterface $message,
        string $channel,
        string $routingKey,
        int $timeout = 0
    ): void;

    /**
     * @param OutboundMessageInterface $message
     * @param InboundMessageInterface $context
     *
     * @return null
     */
    public function publishResponse(
        OutboundMessageInterface $message,
        InboundMessageInterface $context
    );
}
