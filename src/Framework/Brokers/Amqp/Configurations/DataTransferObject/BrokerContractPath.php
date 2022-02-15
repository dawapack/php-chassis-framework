<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class BrokerContractPath extends DataTransferObject
{
    /**
     * @var string
     */
    public string $source;

    /**
     * @var string
     */
    public string $validator;
}
