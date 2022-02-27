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

    public function request(string $operation, array $body): BrokerRequest
    {
        return (new BrokerRequest($body))
            ->fromContext($this->message, $operation);
    }

    /**
     * @param string $operation
     * @param BrokerRequest $message
     *
     * @return BrokerResponse|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RouteNotFoundException
     */
    public function send(string $operation, BrokerRequest $message): ?BrokerResponse
    {
        // set message type
        $message->setMessageType($operation);

        /** @var OutboundRouter $outboundRouter */
        $outboundRouter = $this->app->get(OutboundRouterInterface::class);
        return $outboundRouter->route($message);
    }
}
