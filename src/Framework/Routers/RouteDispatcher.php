<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Application;
use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\MessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Adapters\Outbound\OutboundBusAdapter;
use Chassis\Framework\Adapters\Outbound\OutboundBusAdapterInterface;

class RouteDispatcher implements RouteDispatcherInterface
{
    private Application $application;
    private OutboundBusAdapterInterface $outboundBusAdapter;

    /**
     * @param Application $application
     * @param OutboundBusAdapterInterface $outboundBusAdapter
     */
    public function __construct(
        Application $application,
        OutboundBusAdapterInterface $outboundBusAdapter
    ) {
        $this->application = $application;
        $this->outboundBusAdapter = $outboundBusAdapter;
    }

    /**
     * @inheritdoc
     */
    public function dispatch($service, MessageInterface $message)
    {
        // service resolver
        $resolvedService = $this->serviceResolver($service, $message);
        return $resolvedService->invokable
            ? ($resolvedService->instance)($message, $this->application)
            : $resolvedService->instance->{$resolvedService->method}();
    }

    /**
     * @inheritdoc
     */
    public function dispatchResponse(OutboundMessageInterface $message, InboundMessageInterface $context)
    {
        return $this->outboundBusAdapter->pushResponse($message, $context);
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
        $resolvedService->instance = new $resolvedService->class($message, $this->application);

        return $resolvedService;
    }
}
