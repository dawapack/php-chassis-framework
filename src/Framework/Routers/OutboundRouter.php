<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;

class OutboundRouter implements OutboundRouterInterface
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
        $this->dispatcher = $dispatcher;
        $this->routes = $routes;
    }

    /**
     * @inheritDoc
     */
    public function route(
        ?string $operation,
        OutboundMessageInterface $message,
        InboundMessageInterface $context = null
    ): ?InboundMessageInterface {
        // active rpc handler - respond using context
        if ($context instanceof InboundMessageInterface) {
            return $this->dispatcher->dispatchResponse($message, $context);
        }

        // is route not found?
        if (!isset($this->routes[$operation])) {
            throw new RouteNotFoundException("route not found");
        }

        return $this->dispatcher->dispatch($this->routes[$operation], $message);
    }
}
