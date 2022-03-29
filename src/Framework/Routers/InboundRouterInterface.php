<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;

interface InboundRouterInterface extends RouterInterface
{
    /**
     * @param string|null $operation
     * @param InboundMessageInterface $message
     *
     * @return void
     */
    public function route(?string $operation, InboundMessageInterface $message): void;
}
