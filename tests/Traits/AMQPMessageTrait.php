<?php

declare(strict_types=1);

namespace ChassisTests\Traits;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;

trait AMQPMessageTrait
{
    /**
     * @param string $contentType
     *
     * @return array
     */
    private function createAMQPMessageProperties(string $contentType = 'application/json'): array
    {
        return [
            'content_type' => $contentType,
            'content_encoding' => 'UTF-8',
            'message_id' => Uuid::uuid4()->toString(),
            'correlation_id' => Uuid::uuid4()->toString(),
            'timestamp' => null,
            'expiration' => null,
            'delivery_mode' => 2,
            'app_id' => null,
            'user_id' => null,
            'type' => 'doSomething',
            'reply_to' => null,
            'priority' => 0
        ];
    }

    /**
     * @param object|array|string $body
     * @param array $properties
     * @param array $headers
     *
     * @return AMQPMessage
     */
    private function createAMQPMessage($body, array $properties = [], array $headers = []): AMQPMessage
    {
        $properties = array_merge(
            $this->createAMQPMessageProperties(
                $properties["content_type"] ?? "application/json"
            ),
            $properties
        );
        if (!empty($headers)) {
            $properties["application_headers"] = new AMQPTable($headers);
        }

        return new AMQPMessage($body, $properties);
    }
}
