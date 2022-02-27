<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Handlers;

use PhpAmqpLib\Message\AMQPMessage;

class NullAckHandler implements AckNackHandlerInterface
{
    /**
     * @param AMQPMessage $message
     *
     * @return void
     */
    public function handle(AMQPMessage $message): void
    {
    }
}
