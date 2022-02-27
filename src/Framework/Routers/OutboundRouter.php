<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;

class OutboundRouter implements RouterInterface, OutboundRouterInterface
{
    private RouteDispatcherInterface $dispatcher;
    private array $routes;

    /**
     * @param RouteDispatcherInterface $dispatcher
     * @param array $routes
     */
    public function __construct(
        RouteDispatcherInterface $dispatcher,
        array $routes
    ) {
        $this->routes = $routes;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function route(string $route, MessageBagInterface $message): ?BrokerResponse
    {
        if (!isset($this->routes[$route])) {
            throw new RouteNotFoundException("route not found");
        }
        return $this->dispatcher->dispatch($this->routes[$route], $message, $this);
    }
}
