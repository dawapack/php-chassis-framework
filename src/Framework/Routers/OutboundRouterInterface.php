<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;

interface OutboundRouterInterface
{
    /**
     * @param MessageBagInterface $message
     *
     * @return MessageBagInterface|bool
     *
     * @throws RouteNotFoundException
     */
    public function route(MessageBagInterface $message);
}
