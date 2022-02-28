<?php

declare(strict_types=1);

namespace Chassis\Framework\Providers;

use Chassis\Framework\Routers\InboundRouter;
use Chassis\Framework\Routers\InboundRouterInterface;
use Chassis\Framework\Routers\OutboundRouter;
use Chassis\Framework\Routers\OutboundRouterInterface;
use Chassis\Framework\Routers\RouteDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;
use function Chassis\Helpers\app;

class RoutingServiceProvider extends AbstractServiceProvider
{
    protected array $inboundRoutes = [];
    protected array $outboundRoutes = [];

    public function provides(string $id): bool
    {
        $ids = [
            InboundRouterInterface::class,
            OutboundRouterInterface::class
        ];

        return in_array($id, $ids);
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $application = app();

        $container->add(InboundRouterInterface::class, InboundRouter::class)
            ->addArguments([new RouteDispatcher($application), $this->inboundRoutes]);

        $container->add(OutboundRouterInterface::class, OutboundRouter::class)
            ->addArguments([new RouteDispatcher($application), $this->outboundRoutes]);
    }
}
