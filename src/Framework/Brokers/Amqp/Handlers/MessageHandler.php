<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Handlers;

use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Exceptions\MessageBagFormatException;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;

class MessageHandler implements MessageHandlerInterface
{
    private BrokerResponse $message;

    /**
     * Nobody cares about implementation
     *
     * @param AMQPMessage $message
     *
     * @return void
     * @throws MessageBagFormatException
     * @throws JsonException
     */
    public function __invoke(AMQPMessage $message): void
    {
        $this->message = new BrokerResponse(
            $message->getBody(),
            $message->get_properties(),
            $message->getConsumerTag()
        );
        $message->ack();
    }

    /**
     * @return $this
     */
    public function clear(): MessageHandler
    {
        unset($this->message);
        return $this;
    }

    /**
     * @return BrokerResponse|null
     */
    public function getMessage(): ?BrokerResponse
    {
        return $this->message ?? null;
    }
}
