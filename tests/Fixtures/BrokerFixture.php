<?php

namespace Chassis\Tests\Fixtures;

trait BrokerFixture
{
    public function getBrokerConfigurationFile(): array
    {
        return require __DIR__ . '/Config/broker.php';
    }
}