<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

use Chassis\Framework\Bus\Exceptions\MessageBusException;
use PhpAmqpLib\Message\AMQPMessage;

interface OutboundMessageInterface extends MessageInterface
{
    /**
     * @param mixed $body
     *
     * @return $this
     */
    public function setBody($body): self;

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers): self;

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setHeader(string $name, $value): self;

    /**
     * @param array $properties
     *
     * @return $this
     */
    public function setProperties(array $properties): self;

    /**
     * @param string $name
     * @param mixed|null $value
     *
     * @return $this
     */
    public function setProperty(string $name, $value): self;

    /**
     * @return $this
     */
    public function setDefaultProperties(): self;

    /**
     * @return mixed|AMQPMessage
     *
     * @throws MessageBusException
     */
    public function toMessageBus();
}
