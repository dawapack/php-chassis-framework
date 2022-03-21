<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\MessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Adapters\Outbound\OutboundBusAdapterInterface;
use function Chassis\Helpers\app;

class RouteDispatcher implements RouteDispatcherInterface
{

    /**
     * @inheritdoc
     */
    public function dispatch($service, MessageInterface $message)
    {
        // service resolver
        $resolvedService = $this->serviceResolver($service, $message);
        return $resolvedService->invokable
            ? ($resolvedService->instance)($message, app())
            : $resolvedService->instance->{$resolvedService->method}();
    }

    /**
     * @inheritdoc
     */
    public function dispatchResponse(OutboundMessageInterface $response, InboundMessageInterface $context)
    {
        /** @var OutboundBusAdapterInterface $outboundBusAdapter */
        $outboundBusAdapter = app(OutboundBusAdapterInterface::class);
        return $outboundBusAdapter->pushResponse($response, $context);
    }

    /**
     * @param array|string $service
     * @param MessageInterface $message
     *
     * @return object
     */
    protected function serviceResolver($service, MessageInterface $message): object
    {
        $resolvedService = (object)[
            'invokable' => false,
            'instance' => null,
            'class' => null,
            'method' => null,
        ];
        if (is_string($service)) {
            $resolvedService->invokable = true;
            $resolvedService->instance = new $service();

            return $resolvedService;
        }

        list($resolvedService->class, $resolvedService->method) = $service;
        $resolvedService->instance = new $resolvedService->class($message, app());

        return $resolvedService;
    }
}
