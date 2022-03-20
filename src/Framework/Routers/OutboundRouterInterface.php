<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;

interface OutboundRouterInterface extends RouterInterface
{
    /**
     * @param string|null $operation
     * @param OutboundMessageInterface $message
     * @param InboundMessageInterface $context
     *
     * @return InboundMessageInterface|null
     *
     * @throws RouteNotFoundException
     */
    public function route(
        ?string $operation,
        OutboundMessageInterface $message,
        InboundMessageInterface $context
    ): ?InboundMessageInterface;
}
