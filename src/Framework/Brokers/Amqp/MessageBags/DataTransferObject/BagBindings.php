<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\MessageBags\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class BagBindings extends DataTransferObject
{
    /**
     * @var string|null
     */
    public ?string $channelName;

    /**
     * @var string|null
     */
    public ?string $exchange;

    /**
     * @var string|null
     */
    public ?string $queue;

    /**
     * @var string|null
     */
    public ?string $routingKey;

    /**
     * @var string|null
     */
    public ?string $consumerTag;
}
