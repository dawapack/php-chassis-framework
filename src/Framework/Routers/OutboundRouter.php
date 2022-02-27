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
    public function route(MessageBagInterface $message): ?BrokerResponse
    {
        $operation = $message->getProperty("type");
        if (!isset($this->routes[$operation])) {
            throw new RouteNotFoundException("no route found for operation '$operation'");
        }

        var_dump($this->routes);

        return $this->dispatcher->dispatch($this->routes[$operation], $message, $this);
    }
}
