<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Application;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

use function Chassis\Helpers\app;

class BrokerAbstractService implements ServiceInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "broker_service_";

    protected MessageBagInterface $message;
    protected Application $app;

    /**
     * @param MessageBagInterface $message
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
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
}
