<?php

namespace Chassis\Framework\Providers;

use Chassis\Framework\Routers\RouteDispatcher;
use Chassis\Framework\Routers\Router;
use Chassis\Framework\Routers\RouterInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

abstract class RoutingServiceProvider extends AbstractServiceProvider
{
    protected array $routes = [];

    public function provides(string $id): bool
    {
        return $id === RouterInterface::class;
    }

    public function register(): void
    {
        $this->getContainer()
            ->add(RouterInterface::class, Router::class)
            ->addArgument([new RouteDispatcher(), $this->routes]);
    }
}
