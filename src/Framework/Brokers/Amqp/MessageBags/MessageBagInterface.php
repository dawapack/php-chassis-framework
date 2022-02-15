<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\MessageBags;

use Chassis\Framework\Brokers\Amqp\MessageBags\DataTransferObject\BagBindings;
use Chassis\Framework\Brokers\Amqp\MessageBags\DataTransferObject\BagProperties;
use PhpAmqpLib\Message\AMQPMessage;

interface MessageBagInterface
{
    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getProperty(string $key);

    /**
     * @return BagProperties
     */
    public function getProperties(): BagProperties;

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getBinding(string $key);

    /**
     * @return BagBindings
     */
    public function getBindings(): BagBindings;

    /**
     * @return mixed
     */
    public function getBody();

    /**
     * @param string $routingKey
     *
     * @return $this
     */
    public function setRoutingKey(string $routingKey): self;

    /**
     * @return string|null
     */
    public function getRoutingKey(): ?string;

    /**
     * @param string $messageType
     *
     * @return $this
     */
    public function setMessageType(string $messageType): self;

    /**
     * @param string $replyTo
     *
     * @return $this
     */
    public function setReplyTo(string $replyTo): self;

    /**
     * @param string $channelName
     *
     * @return $this
     */
    public function setChannelName(string $channelName): self;

    /**
     * @param string $exchangeName
     *
     * @return $this
     */
    public function setExchangeName(string $exchangeName): self;

    /**
     * @param string $queueName
     *
     * @return $this
     */
    public function setQueueName(string $queueName): self;

    /**
     * @return AMQPMessage
     */
    public function toAmqpMessage(): AMQPMessage;
}
