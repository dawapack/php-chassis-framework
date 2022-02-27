<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Application;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;
use Chassis\Framework\Routers\OutboundRouter;
use Chassis\Framework\Routers\OutboundRouterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Chassis\Helpers\app;

class BrokerAbstractService implements ServiceInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "broker_service_";

    protected MessageBagInterface $message;
    protected Application $app;

    /**
     * @param MessageBagInterface $message
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        MessageBagInterface $message
    ) {
        $this->message = $message;
        $this->app = app();
    }

    public function response($body = []): BrokerResponse
    {
        return (new BrokerResponse($body, []))
            ->fromContext($this->message);
    }

    public function request(array $body): BrokerRequest
    {
        return (new BrokerRequest($body))
            ->fromContext($this->message);
    }

    /**
     * @param string $route
     * @param BrokerRequest $message
     *
     * @return BrokerResponse|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RouteNotFoundException
     */
    public function send(string $route, BrokerRequest $message): ?BrokerResponse
    {
        /** @var OutboundRouter $outboundRouter */
        $outboundRouter = $this->app->get(OutboundRouterInterface::class);
        return $outboundRouter->route($route, $message);
    }
}
