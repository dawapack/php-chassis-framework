<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus;

use Chassis\Framework\Bus\AMQP\Message\Exceptions\MessageBodyContentTypeException;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;

interface MessageBusInterface
{
    /**
     * @param AMQPMessage $messageBus
     *
     * @return void
     */
    public function setMessage(AMQPMessage $messageBus);

    /**
     * @return string|array|null
     *
     * @throws JsonException
     * @throws MessageBodyContentTypeException
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
     * @throws MessageBodyContentTypeException
     */
    public function convert($body, array $properties, array $headers);
}