<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\MessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;

interface RouteDispatcherInterface
{
    /**
     * @param array|string $operationHandler
     * @param MessageInterface $message
     *
     * @return InboundMessageInterface|OutboundMessageInterface|null
     */
    public function dispatch($operationHandler, MessageInterface $message);

    /**
     * @param OutboundMessageInterface $response
     * @param InboundMessageInterface $context
     *
     * @return null
     */
    public function dispatchResponse(OutboundMessageInterface $response, InboundMessageInterface $context);
}
