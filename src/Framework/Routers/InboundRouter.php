<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Routers\Routes\RouteNotFound;
use Psr\Log\LoggerInterface;
use Throwable;

use function Chassis\Helpers\app;

class InboundRouter implements InboundRouterInterface
{
    private const LOGGER_COMPONENT_PREFIX = 'inbound_router_';

    private RouteDispatcherInterface $dispatcher;
    private LoggerInterface $logger;
    private array $routes;

    /**
     * @param RouteDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param array $routes
     */
    public function __construct(
        RouteDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        array $routes
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->routes = $routes;
    }

    /**
     * @inheritDoc
     */
    public function route(?string $operation, InboundMessageInterface $message): void
    {
        $operationHandler = $this->routes[$operation] ?? RouteNotFound::class;
        $response = $this->dispatcher->dispatch($operationHandler, $message);

        try {
            if ($response instanceof OutboundMessageInterface) {
                /** @var OutboundRouter $outboundRouter */
                $outboundRouter = app(OutboundRouterInterface::class);
                $outboundRouter->route(null, $response, $message);
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
