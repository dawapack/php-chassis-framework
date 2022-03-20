<?php

declare(strict_types=1);

namespace ChassisTests\Fixtures\Adapters;

use Chassis\Application;
use Chassis\Framework\Adapters\Message\MessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Bus\AMQP\Message\AMQPMessageBus;

class NullOperation
{
    private MessageInterface $message;
    private Application $application;

    /**
     * Nobody cares about implementation
     *
     * @param MessageInterface $message
     * @param Application $application
     */
    public function __invoke(MessageInterface $message, Application $application)
    {
        $this->message = $message;
        $this->application = $application;
    }
}
