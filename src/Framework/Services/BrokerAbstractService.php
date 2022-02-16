<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Application;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

use function Chassis\Helpers\app;

class BrokerAbstractService implements ServiceInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "broker_service_";

    protected MessageBagInterface $messageBag;
    protected Application $app;

    /**
     * @param MessageBagInterface $messageBag
     */
    public function __construct(
        MessageBagInterface $messageBag
    ) {
        $this->messageBag = $messageBag;
        $this->app = app();
    }

    public function response($body = ""): BrokerResponse
    {
        return new BrokerResponse($body, []);
    }
}
