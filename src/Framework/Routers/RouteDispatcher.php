<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Services\ServiceInterface;

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
        $response = !$this->invokable
            ? $concreteService->{$this->method}()
            : ($concreteService)($messageBag);

        // handle response
        if ($response instanceof MessageBagInterface) {
            $this->dispatchResponse($response, $messageBag);
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
            $className = $route;
        } else {
            list($className, $this->method) = $route;
        }

        return new $className($messageBag);
    }

    /**
     * @param MessageBagInterface $response
     *
     * @return void
     */
    private function dispatchResponse(MessageBagInterface $response, MessageBagInterface $context): void
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
    }
}
