<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Outbound\AckNackHandlers;

use PhpAmqpLib\Message\AMQPMessage;

class PublishAckHandler implements PublishConfirmationHandlerInterface
{
    /**
     * Nobody cares about implementation
     *
     * @param AMQPMessage $message
     *
     * @return void
     */
    public function __invoke(AMQPMessage $message): void
    {
    }
}
