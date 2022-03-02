<?php

namespace Chassis\Tests\Framework\Brokers\Amqp\Configurations;

use Chassis\Framework\Brokers\Amqp\Configurations\BrokerConfiguration;
use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerConnection;
use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerContract;
use Chassis\Tests\BaseTestCase;
use Chassis\Tests\Fixtures\BrokerFixture;

class BrokerConfigurationTest extends BaseTestCase
{
    use BrokerFixture;

    private BrokerConfiguration $subject;

    protected function setUp(): void
    {
        $this->subject = new BrokerConfiguration($this->getBrokerConfigurationFile());
    }

    public function testGetContract(): void
    {
        $this->assertSame('asyncapi', $this->subject->getContract());
    }

    public function testGetConnection(): void
    {
        $this->assertSame('amqp', $this->subject->getConnection());
    }

    public function testGetContractConfiguration(): void
    {
        $connectionConfiguration = $this->subject->getContractConfiguration();
        $this->assertInstanceOf(BrokerContract::class, $connectionConfiguration);
    }

    public function testGetConnectionConfiguration(): void
    {
        $connectionConfiguration = $this->subject->getConnectionConfiguration();
        $this->assertInstanceOf(BrokerConnection::class, $connectionConfiguration);
    }
}