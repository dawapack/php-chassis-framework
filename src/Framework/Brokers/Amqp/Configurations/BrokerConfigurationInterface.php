<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Configurations;

use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerConnection;
use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerContract;

interface BrokerConfigurationInterface
{
    /**
     * @return string
     */
    public function getContract(): string;

    /**
     * @return BrokerContract
     */
    public function getContractConfiguration(): BrokerContract;

    /**
     * @return string
     */
    public function getConnection(): string;

    /**
     * @return BrokerConnection
     */
    public function getConnectionConfiguration(): BrokerConnection;
}
