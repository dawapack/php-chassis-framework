<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\MessageBags\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class BagProperties extends DataTransferObject
{
    /**
     * @example 'text/plain', 'application/json', 'application/gzip'
     * @var string|null
     */
    public string $content_type;

    /**
     * @example 'UTF-8', 'ISO...'
     * @var string|null
     */
    public string $content_encoding;

    /**
     * @format UUID
     * @var string
     */
    public string $message_id;

    /**
     * @format UUID
     * @var string
     */
    public string $correlation_id;

    /**
     * @example timestamp
     * @var int|null
     */
    public ?int $timestamp;

    /**
     * @example timestamp + X seconds
     * @var int|null
     */
    public ?int $expiration;

    /**
     * @var int|null
     */
    public int $delivery_mode;

    /**
     * @example 'my-application-name'
     * @var string|null
     */
    public ?string $app_id;

    /**
     * @var string|null
     */
    public ?string $user_id;

    /**
     * @example message type discriminator like 'user.created'
     * @var string
     */
    public string $type;

    /**
     * @var string|null
     */
    public ?string $reply_to;

    /**
     * @var array
     */
    public array $application_headers = [];

    /**
     * @description 0 to 10
     * @var int
     */
    public int $priority;

    /**
     * @var string|null
     */
    public ?string $cluster_id;
}
