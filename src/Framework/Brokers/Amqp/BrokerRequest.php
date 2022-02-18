<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp;

use Chassis\Framework\Brokers\Amqp\MessageBags\AbstractMessageBag;
use Chassis\Framework\Brokers\Amqp\MessageBags\RequestMessageBagInterface;

class BrokerRequest extends AbstractMessageBag implements RequestMessageBagInterface
{
    /**
     * @inheritdoc
     */
    public function setMessageType(string $messageType): BrokerRequest
    {
        $this->properties->type = $messageType;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setReplyTo(string $replyTo): BrokerRequest
    {
        $this->properties->reply_to = $replyTo;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setChannelName(string $channelName): BrokerRequest
    {
        $this->bindings->channelName = $channelName;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRoutingKey(string $routingKey): BrokerRequest
    {
        $this->bindings->routingKey = $routingKey;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setExchangeName(string $exchangeName): BrokerRequest
    {
        $this->bindings->exchange = $exchangeName;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setQueueName(string $queueName): BrokerRequest
    {
        $this->bindings->queue = $queueName;
        return $this;
    }

    public function send(string $channelName, string $routingKey): BrokerResponse
    {
        // TODO: implement send mechanism - use RemoteProcedureCallStreamer::class
        return new BrokerResponse([]);
    }
}
