<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\MessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Adapters\Outbound\Bus\OutboundBusAdapter;
use Chassis\Framework\Adapters\Outbound\Bus\OutboundBusAdapterInterface;

use function Chassis\Helpers\app;

class RouteDispatcher implements RouteDispatcherInterface
{
    /**
     * @inheritdoc
     */
    public function dispatch($operationHandler, MessageInterface $message)
    {
        // handler resolver
        $handler = $this->handlerResolver($operationHandler, $message);
        return $handler->invokable
            ? ($handler->instance)($message)
            : $handler->instance->{$handler->method}();
    }

    /**
     * @inheritdoc
     */
    public function dispatchResponse(OutboundMessageInterface $response, InboundMessageInterface $context)
    {
        /** @var OutboundBusAdapter $outboundBusAdapter */
        $outboundBusAdapter = app(OutboundBusAdapterInterface::class);
        return $outboundBusAdapter->pushResponse($response, $context);
    }

    /**
     * @param array|string $operationHandler
     * @param MessageInterface $message
     *
     * @return object
     */
    protected function handlerResolver($operationHandler, MessageInterface $message): object
    {
        $resolvedHandler = (object)[
            'invokable' => false,
            'instance' => null,
            'class' => null,
            'method' => null,
        ];
        if (is_string($operationHandler)) {
            $resolvedHandler->invokable = true;
            $resolvedHandler->instance = new $operationHandler();

            return $resolvedHandler;
        }

        list($resolvedHandler->class, $resolvedHandler->method) = $operationHandler;
        $resolvedHandler->instance = new $resolvedHandler->class($message);

        return $resolvedHandler;
    }
}
