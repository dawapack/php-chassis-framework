<?php

declare(strict_types=1);

namespace Chassis\Framework\AsyncApi\Transformers;

use Chassis\Framework\AsyncApi\TransformersInterface;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPTransformer implements TransformersInterface
{
    private object $channelBindings;
    private object $operationBindings;
    private object $messageBindings;
    private array $connection;
    private array $consumerOptions = [
        'queue' => '',
        'consumer_tag' => '',
        'no_local' => false,
        'no_ack' => false,
        'exclusive' => false,
        'nowait' => false,
        'callback' => null,
        'ticket' => null,
        'arguments' => [],
    ];
    private array $connectionOptions = [
        'host' => null,
        'port' => 0,
        'user' => '',
        'pass' => '',
        'vhost' => '/',
        'insist' => false,
        'login_method' => 'AMQPLAIN',
        'login_response' => null,
        'locale' => 'en_US',
        'connection_timeout' => 3.0,
        'read_write_timeout' => 30.0,
        'context' => null,
        'keepalive' => false,
        'heartbeat' => 0,
        'channel_rpc_timeout' => 30.0,
        'ssl_protocol' => null,
    ];
    private array $exchangeDeclareOptions = [
        'name' => null,
        'type' => null,
        'passive' => true,
        'durable' => true,
        'autoDelete' => false,
        'internal' => false,
        'nowait' => false,
        'arguments' => [],
        'ticket' => null
    ];
    private array $queueDeclareOptions = [
        'name' => null,
        'passive' => true,
        'durable' => true,
        'exclusive' => false,
        'autoDelete' => false,
        'nowait' => false,
        'arguments' => [],
        'ticket' => null
    ];

    /**
     * @inheritdoc
     */
    public function setBindings(object $channel, object $operation, object $message): AMQPTransformer
    {
        $this->channelBindings = $channel;
        $this->operationBindings = $operation;
        $this->messageBindings = $message;

        return $this;
    }

    public function setConnection(array $connection): AMQPTransformer
    {
        $this->connection = array_merge(
            $this->connectionOptions,
            array_intersect_key(
                $connection,
                $this->connectionOptions
            )
        );

        return $this;
    }

    public function toConnectionArguments(bool $onlyValues = true): array
    {
        return $onlyValues ? array_values($this->connection) : $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function toPublishArguments(AMQPMessage $message, string $routingKey, bool $onlyValues = true): array
    {
        $arguments = [
            'message' => $message,
            'exchange' => $this->channelBindings->name,
            'routingKey' => $routingKey,
            'mandatory' => $this->operationBindings->mandatory ?? false,
            'immediate' => $this->operationBindings->immediate ?? false,
            'ticket' => null,
        ];

        return $onlyValues ? array_values($arguments) : $arguments;
    }

    /**
     * @inheritDoc
     */
    public function toConsumeArguments(array $options, bool $onlyValues = true): array
    {
        $arguments = array_merge(
            $this->consumerOptions,
            array_intersect_key(
                $options,
                $this->consumerOptions
            ),
            [
                'queue' => $this->channelBindings->name,
                'no_ack' => !($this->operationBindings->ack ?? true),
            ]
        );

        return $onlyValues ? array_values($arguments) : $arguments;
    }

    /**
     * @inheritDoc
     */
    public function toExchangeDeclareArguments(array $options, bool $onlyValues = true): array
    {
        $arguments = array_merge(
            $this->exchangeDeclareOptions,
            (array)$this->channelBindings,
            array_intersect_key(
                $options,
                $this->exchangeDeclareOptions
            )
        );

        return $onlyValues ? array_values($arguments) : $arguments;
    }

    /**
     * @inheritDoc
     */
    public function toQueueDeclareArguments(array $options, bool $onlyValues = true): array
    {
        $arguments = array_merge(
            $this->queueDeclareOptions,
            array_intersect_key(
                (array)$this->channelBindings,
                $this->queueDeclareOptions
            ),
            array_intersect_key(
                $options,
                $this->queueDeclareOptions
            )
        );

        return $onlyValues ? array_values($arguments) : $arguments;
    }

    /**
     * @inheritDoc
     */
    public function toCallbackQueueDeclareArguments(array $options, bool $onlyValues = true): array
    {
        $arguments = array_merge(
            $this->queueDeclareOptions,
            array_intersect_key(
                $options,
                $this->queueDeclareOptions
            ),
            [
                'name' => '',
                'passive' => false,
                'durable' => false,
                'exclusive' => true,
                'autoDelete' => false,
            ]
        );

        return $onlyValues ? array_values($arguments) : $arguments;
    }
}
