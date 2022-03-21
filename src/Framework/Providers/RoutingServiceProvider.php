<?php

declare(strict_types=1);

namespace Chassis\Framework\Providers;

use Chassis\Framework\Routers\InboundRouter;
use Chassis\Framework\Routers\InboundRouterInterface;
use Chassis\Framework\Routers\OutboundRouter;
use Chassis\Framework\Routers\OutboundRouterInterface;
use Chassis\Framework\Routers\RouteDispatcher;
use Chassis\Framework\Routers\RouteDispatcherInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Log\LoggerInterface;

class RoutingServiceProvider extends AbstractServiceProvider
{
    protected array $inboundRoutes = [];
    protected array $outboundRoutes = [];

    public function provides(string $id): bool
    {
        $ids = [
            RouteDispatcherInterface::class,
            InboundRouterInterface::class,
            OutboundRouterInterface::class
        ];

        return in_array($id, $ids);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(RouteDispatcherInterface::class, RouteDispatcher::class);

        $container->add(OutboundRouterInterface::class, OutboundRouter::class)
            ->addArguments([RouteDispatcherInterface::class, $this->outboundRoutes]);

        $container->add(InboundRouterInterface::class, InboundRouter::class)
            ->addArguments([
                RouteDispatcherInterface::class,
                OutboundRouterInterface::class,
                LoggerInterface::class,
                $this->inboundRoutes
            ]);
    }
}
