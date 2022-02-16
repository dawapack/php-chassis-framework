<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;
use Chassis\Framework\Services\RoutingService;

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
        $this->dispatcher = $dispatcher;
        $this->routes = $routes;
    }

    /**
     * @inheritDoc
     */
    public function route(MessageBagInterface $messageBag): bool
    {
        if (!isset($this->routes[$messageBag->getProperty("type")])) {
            return $this->dispatcher->dispatch(
                [RoutingService::class, "routeNotfound"],
                $messageBag
            );
        }
        return $this->dispatcher->dispatch($this->routes[$messageBag->getProperty("type")], $messageBag);
    }
}
