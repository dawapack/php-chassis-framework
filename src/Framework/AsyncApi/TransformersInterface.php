<?php

declare(strict_types=1);

namespace Chassis\Framework\AsyncApi;

use PhpAmqpLib\Message\AMQPMessage;

interface TransformersInterface
{
    /**
     * @param object $channel
     * @param object $operation
     * @param object $message
     *
     * @return $this
     */
    public function setBindings(object $channel, object $operation, object $message): self;

    /**
     * @param array $connection
     *
     * @return $this
     */
    public function setConnection(array $connection): self;

    /**
     * @param bool $onlyValues
     *
     * @return array
     */
    public function toConnectionArguments(bool $onlyValues = true): array;

    /**
     * @param AMQPMessage $message ,
     * @param string $routingKey
     * @param bool $onlyValues
     *
     * @return array
     */
    public function toPublishArguments(AMQPMessage $message, string $routingKey, bool $onlyValues = true): array;

    /**
     * @param array $options
     * @param bool $onlyValues
     *
     * @return array
     */
    public function toConsumeArguments(array $options, bool $onlyValues = true): array;

    /**
     * @param array $options
     * @param bool $onlyValues
     *
     * @return array
     */
    public function toExchangeDeclareArguments(array $options, bool $onlyValues = true): array;

    /**
     * @param array $options
     * @param bool $onlyValues
     *
     * @return array
     */
    public function toQueueDeclareArguments(array $options, bool $onlyValues = true): array;
}
