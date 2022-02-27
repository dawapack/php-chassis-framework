<?php

declare(strict_types=1);

namespace Chassis\Framework\OutboundAdapters;

use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

interface BrokerOutboundAdapterInterface
{
    /**
     * @param BrokerResponse|BrokerRequest $message
     * @param int $timeout
     *
     * @return BrokerResponse|null
     */
    public function send(MessageBagInterface $message, int $timeout = 30): ?BrokerResponse;

    /**
     * @param int $timeout
     *
     * @return BrokerResponse|null
     */
    public function pull(int $timeout = 30): ?BrokerResponse;
}
