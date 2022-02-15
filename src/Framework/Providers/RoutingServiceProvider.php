<?php

namespace Chassis\Framework\Providers;

use Chassis\Framework\Routers\RouteDispatcher;
use Chassis\Framework\Routers\Router;
use Chassis\Framework\Routers\RouterInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

use League\Container\ServiceProvider\BootableServiceProviderInterface;
use function Chassis\Helpers\app;

abstract class RoutingServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    protected array $routes = [];

    public function provides(string $id): bool
    {
        return $id === RouterInterface::class;
    }

    public function boot(): void
    {
        var_dump([__METHOD__, $this->routes]);
        sleep(1);
    }

    public function register(): void
    {
        var_dump([__METHOD__, $this->routes]);
        sleep(1);

        $this->getContainer()
            ->add(RouterInterface::class, Router::class)
            ->addArguments([
                new RouteDispatcher(app()),
                $this->routes
            ])->setShared(false);
    }
}
