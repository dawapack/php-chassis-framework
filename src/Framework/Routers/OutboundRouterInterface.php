<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;

interface OutboundRouterInterface
{
    /**
     * @param string $route
     * @param MessageBagInterface $message
     *
     * @return BrokerResponse|null
     *
     * @throws RouteNotFoundException
     */
    public function route(string $route, MessageBagInterface $message): ?BrokerResponse;
}
