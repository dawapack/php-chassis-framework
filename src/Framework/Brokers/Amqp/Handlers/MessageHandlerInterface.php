<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Handlers;

use PhpAmqpLib\Message\AMQPMessage;

interface MessageHandlerInterface
{
    /**
     * @param AMQPMessage $message
     *
     * @return void
     */
    public function handle(AMQPMessage $message): void;
}
