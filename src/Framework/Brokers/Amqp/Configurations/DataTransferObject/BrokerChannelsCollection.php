<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class BrokerChannelsCollection extends DataTransferObjectCollection
{
    /**
     * @return BrokerChannel
     */
    public function current(): BrokerChannel
    {
        return parent::current();
    }
}
