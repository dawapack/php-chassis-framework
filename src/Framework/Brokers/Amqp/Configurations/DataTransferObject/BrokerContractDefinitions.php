<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class BrokerContractDefinitions extends DataTransferObject
{
    /**
     * @var string
     */
    public string $contract;

    /**
     * @var string|null
     */
    public ?string $infrastructure;
}
