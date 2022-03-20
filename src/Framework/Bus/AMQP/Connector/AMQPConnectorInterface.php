<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Connector;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;

interface AMQPConnectorInterface
{
    /**
     * @param int|null $id
     *
     * @return AMQPChannel
     */
    public function getChannel(?int $id = null): AMQPChannel;

    /**
     * @return void
     *
     * @throws Exception
     */
    public function disconnect(): void;

    /**
     * @return void
     */
    public function checkHeartbeat(): void;
}
