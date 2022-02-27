<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

interface InboundRouterInterface
{
    /**
     * @param MessageBagInterface $message
     *
     * @return BrokerRequest|BrokerResponse|null
     */
    public function route(MessageBagInterface $message);
}
