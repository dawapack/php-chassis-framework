<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

use function Chassis\Helpers\publish;

class RouteDispatcher implements RouteDispatcherInterface
{
    /**
     * @inheritdoc
     */
    public function dispatch($route, MessageBagInterface $message, RouterInterface $router)
    {
        // broker service resolver
        $service = $this->resolveRoute($route, $message);

        var_dump($service);

        $response = $service["invokable"]
            ? ($service["instance"])($message)
            : $service["instance"]->{$service["method"]}();

        // return outbound router response
        if ($router instanceof OutboundRouter) {
            return $response;
        }

        // dispatch inbound router response if any
        if ($response instanceof BrokerResponse) {
            $this->dispatchResponse($response);
        }

        return null;
    }

    /**
     * @param array|string $route
     * @param MessageBagInterface $message
     *
     * @return array
     */
    protected function resolveRoute($route, MessageBagInterface $message): array
    {
        $service = ['invokable' => false];
        if (is_string($route)) {
            $service["invokable"] = true;
            $service["instance"] = new $route();

            return $service;
        }

        list($service["class"], $service["method"]) = $route;
        $service["instance"] = new $service["class"]($message);

        return $service;
    }

    /**
     * @param BrokerResponse $response
     *
     * @return void
     */
    protected function dispatchResponse(BrokerResponse $response): void
    {
        publish($response);
    }
}
