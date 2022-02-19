<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;
use Chassis\Framework\Services\RoutingService;
use phpDocumentor\Reflection\Types\This;

class Router implements RouterInterface
{
    private RouteDispatcher $dispatcher;
    private array $routes;

    /**
     * @param RouteDispatcher $dispatcher
     * @param array $routes
     */
    public function __construct(
        RouteDispatcher $dispatcher,
        array $routes
    ) {
        $this->routes = $routes;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function route(MessageBagInterface $message): bool
    {
        $route = $this->routes[$message->getProperty("type")] ?? [RoutingService::class, "routeNotfound"];

        return $this->dispatcher->dispatch($route, $message);
    }
}
