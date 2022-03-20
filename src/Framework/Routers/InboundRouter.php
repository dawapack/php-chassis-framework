<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\OutboundAdapters\Broker\RouteNotFound;
use Psr\Log\LoggerInterface;
use Throwable;

class InboundRouter implements InboundRouterInterface
{
    private const LOGGER_COMPONENT_PREFIX = 'inbound_router_';

    private RouteDispatcherInterface $dispatcher;
    private OutboundRouterInterface $outboundRouter;
    private LoggerInterface $logger;
    private array $routes;

    /**
     * @param RouteDispatcherInterface $dispatcher
     * @param OutboundRouterInterface $outboundRouter
     * @param LoggerInterface $logger
     * @param array $routes
     */
    public function __construct(
        RouteDispatcherInterface $dispatcher,
        OutboundRouterInterface $outboundRouter,
        LoggerInterface $logger,
        array $routes
    ) {
        $this->dispatcher = $dispatcher;
        $this->outboundRouter = $outboundRouter;
        $this->logger = $logger;
        $this->routes = $routes;
    }

    /**
     * @inheritDoc
     */
    public function route(?string $operation, InboundMessageInterface $message): void
    {
        $service = $this->routes[$operation] ?? RouteNotFound::class;
        $response = $this->dispatcher->dispatch($service, $message);

        try {
            if ($response instanceof OutboundMessageInterface) {
                $this->outboundRouter->route(null, $response, $message);
            }
        } catch (Throwable $reason) {
            $this->logger->error(
                $reason->getMessage(),
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "route_response_exception",
                    "error" => $reason
                ]
            );
        }
    }
}
