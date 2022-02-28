<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Application;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamerInterface;

use function Chassis\Helpers\app;

class RouteDispatcher implements RouteDispatcherInterface
{
    private Application $application;

    /**
     * @inheritdoc
     */
    public function dispatch($route, MessageBagInterface $message, RouterInterface $router)
    {
        $this->application = app();

        // broker service resolver
        $service = $this->resolveRoute($route, $message);
        $response = $service["invokable"]
            ? ($service["instance"])($message, $this->application)
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
        $service["instance"] = new $service["class"]($message, $this->application);

        return $service;
    }

    /**
     * @param BrokerResponse $response
     *
     * @return void
     */
    protected function dispatchResponse(BrokerResponse $response): void
    {
        /** @var PublisherStreamer $publisher */
        $publisher = $this->application->get(PublisherStreamerInterface::class);
        $publisher->publish($response);

    }
}
