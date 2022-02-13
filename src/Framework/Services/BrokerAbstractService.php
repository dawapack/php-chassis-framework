<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Application;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

class BrokerAbstractService implements ServiceInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "broker_service_";

    protected MessageBagInterface $messageBag;
    protected Application $app;

    /**
     * @param MessageBagInterface $messageBag
     * @param Application $app
     */
    public function __construct(
        MessageBagInterface $messageBag,
        Application $app
    ) {
        $this->messageBag = $messageBag;
        $this->app = $app;
    }
}
