<?php

namespace Chassis\Framework\Providers;

use Chassis\Framework\Routers\OutboundRouter;
use Chassis\Framework\Routers\OutboundRouterInterface;
use Chassis\Framework\Routers\RouteDispatcher;
use Chassis\Framework\Routers\InboundRouterInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

abstract class OutboundRoutingServiceProvider extends AbstractServiceProvider
{
    protected array $routes = [];

    public function provides(string $id): bool
    {
        return $id === InboundRouterInterface::class;
    }

    public function register(): void
    {
        $this->getContainer()
            ->add(OutboundRouterInterface::class, OutboundRouter::class)
            ->addArguments([new RouteDispatcher(), $this->routes]);
    }
}
