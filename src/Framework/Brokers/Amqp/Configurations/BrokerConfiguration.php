<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Configurations;

use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerConnection;
use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerContract;

class BrokerConfiguration implements BrokerConfigurationInterface
{
    private array $configuration;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getContract(): string
    {
        return $this->configuration['contract'];
    }

    /**
     * @inheritdoc
     */
    public function getContractConfiguration(): BrokerContract
    {
        return new BrokerContract($this->configuration['contracts'][$this->getContract()]);
    }

    /**
     * @inheritdoc
     */
    public function getConnection(): string
    {
        return $this->configuration['connection'];
    }

    /**
     * @inheritdoc
     */
    public function getConnectionConfiguration(): BrokerConnection
    {
        return new BrokerConnection($this->configuration['connections'][$this->getConnection()]);
    }
}
