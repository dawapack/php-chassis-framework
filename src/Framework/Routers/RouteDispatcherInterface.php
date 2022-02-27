<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

interface RouteDispatcherInterface
{
    /**
     * @param array|string|null $route
     * @param MessageBagInterface $message
     * @param RouterInterface $router
     *
     * @return MessageBagInterface|bool
     */
    public function dispatch(?string $route, MessageBagInterface $message, RouterInterface $router);
}
