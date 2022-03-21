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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

use function Chassis\Helpers\app;

class RoutingServiceProvider extends AbstractServiceProvider
{
    protected array $inboundRoutes = [];
    protected array $outboundRoutes = [];

    /**
     * @param string $id
     *
     * @return bool
     */
    public function provides(string $id): bool
    {
        $ids = [
            RouteDispatcherInterface::class,
            InboundRouterInterface::class,
            OutboundRouterInterface::class
        ];

        return in_array($id, $ids);
    }

    /**
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(RouteDispatcherInterface::class, function ($application) {
            return new RouteDispatcher($application);
        })->addArgument(app());

        $container->add(OutboundRouterInterface::class, OutboundRouter::class)
            ->addArguments([
                RouteDispatcherInterface::class,
                $this->outboundRoutes
            ]);

        $container->add(InboundRouterInterface::class, InboundRouter::class)
            ->addArguments([
                RouteDispatcherInterface::class,
                LoggerInterface::class,
                $this->inboundRoutes
            ]);
    }
}
