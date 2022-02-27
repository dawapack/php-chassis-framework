<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;

class InboundRouter implements RouterInterface, InboundRouterInterface
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
    public function route(MessageBagInterface $message): bool
    {
        $route = $this->routes[$message->getProperty("type")] ?? RouteNotFoundException::class;

        var_dump([__METHOD__, $this->routes, $route]);

        return $this->dispatcher->dispatch($route, $message, $this);
    }
}
