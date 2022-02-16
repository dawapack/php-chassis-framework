<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Services\ServiceInterface;
use function Chassis\Helpers\app;

class RouteDispatcher
{
    private string $method;
    private bool $invokable = false;

    /**
     * @param array|string $route
     * @param MessageBagInterface $messageBag
     *
     * @return bool
     */
    public function dispatch($route, MessageBagInterface $messageBag): bool
    {
        // broker service resolver
        $concreteService = $this->resolveRoute($route, $messageBag);
        $response = $this->invokable
            ? ($concreteService)($messageBag)
            : $concreteService->{$this->method}();

        // handle response
        if ($response instanceof BrokerResponse) {
            $this->dispatchResponse($response);
        }

        return true;
    }

    /**
     * @param array|string $route
     * @param MessageBagInterface $messageBag
     *
     * @return ServiceInterface
     */
    private function resolveRoute($route, MessageBagInterface $messageBag): ServiceInterface
    {
        if (is_string($route)) {
            $this->invokable = true;
            return new $route();
        }

        list($className, $this->method) = $route;
        return new $className($messageBag);
    }

    /**
     * @param BrokerResponse $response
     *
     * @return void
     */
    private function dispatchResponse(BrokerResponse $response): void
    {
        /**
         * use (AMQPdefault) exchange to send the message
         * copy correlation_id from context
         * set routing_key from context reply_to
         *  - on empty reply to, log the response as warning and exit
         *
         * we MUST use publish() helper to send the response
         */

        // TODO: implement broker response

        app()->logger()->info(
            "dispatch response triggered",
            [
                "component" => "route_dispatcher_dispatch_response_info",
                "response" => [
                    "bindings" => $response->getBindings()->toArray(),
                    "properties" => $response->getProperties()->toArray(),
                    "body" => $response->getBody(),
                ]
            ]
        );
    }
}
