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
     * @return string|null
     */
    public function getRoutingKey(): ?string;

    /**
     * @return mixed
     */
    public function getBody();

    /**
     * @param array|string $body
     * @param string $contentType
     * @param string $contentEncoding
     *
     * @return $this
     */
    public function setBody(
        $body,
        string $contentType = "application/json",
        string $contentEncoding = "UTF-8"
    ): self;

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setHeader(string $name, $value): self;

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers): self;

    /**
     * @return AMQPMessage
     */
    public function toAmqpMessage(): AMQPMessage;
}
