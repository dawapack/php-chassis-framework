<?php

namespace Chassis\Framework\Providers;

use Chassis\Framework\Routers\RouteDispatcher;
use Chassis\Framework\Routers\InboundRouter;
use Chassis\Framework\Routers\InboundRouterInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

abstract class InboundRoutingServiceProvider extends AbstractServiceProvider
{
    protected array $routes = [];

    public function provides(string $id): bool
    {
        return $id === InboundRouterInterface::class;
    }

    public function register(): void
    {
        $this->getContainer()
            ->add(InboundRouterInterface::class, InboundRouter::class)
            ->addArguments([new RouteDispatcher(), $this->routes]);
    }
}
