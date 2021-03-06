<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus;

use Chassis\Framework\Bus\Exceptions\MessageBusException;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;

interface MessageBusInterface
{
    /**
     * @param mixed $messageBus
     *
     * @return $this
     */
    public function setMessage($messageBus): self;

    /**
     * @return bool
     */
    public function hasMessage(): bool;

    /**
     * @return string|array|null
     *
     * @throws JsonException
     * @throws MessageBusException
     */
    public function getBody();

    /**
     * @return array
     */
    public function getProperties(): array;

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getProperty(string $name);

    /**
     * @return array
     */
    public function getDefaultProperties(): array;

    /**
     * @return array
     */
    public function getHeaders(): ?array;

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getHeader(string $name);

    /**
     * @param string|array|object $body
     * @param array $properties
     * @param array $headers
     *
     * @return mixed
     *
     * @throws MessageBusException
     */
    public function convert($body, array $properties, array $headers);
}
