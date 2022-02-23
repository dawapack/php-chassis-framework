<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\MessageBags;

interface RequestMessageBagInterface
{
    /**
     * @param MessageBagInterface $messageBag
     * @param string $operation
     *
     * @return $this
     */
    public function fromContext(MessageBagInterface $messageBag, string $operation): self;

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
     * @param string $routingKey
     *
     * @return $this
     */
    public function setRoutingKey(string $routingKey): self;

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
}
