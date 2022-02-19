<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;
use Chassis\Framework\Services\RoutingService;

class Router implements RouterInterface
{
    private array $routes;

    /**
     * @param array $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @inheritDoc
     */
    public function route(MessageBagInterface $message): bool
    {
        if (!isset($this->routes[$message->getProperty("type")])) {
            return (new RouteDispatcher())
                ->dispatch(
                    [RoutingService::class, "routeNotfound"],
                    $message
                );
        }
        return (new RouteDispatcher())
            ->dispatch(
                $this->routes[$message->getProperty("type")],
                $message
            );
    }
}
