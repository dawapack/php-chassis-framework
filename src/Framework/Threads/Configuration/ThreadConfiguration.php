<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads\Configuration;

use Spatie\DataTransferObject\DataTransferObject;

class ThreadConfiguration extends DataTransferObject
{
    /**
     * @var string
     */
    public string $threadType;

    /**
     * @var int
     */
    public int $minimum;

    /**
     * @var int
     */
    public int $maximum;

    /**
     * @var array
     */
    public array $triggers;

    /**
     * @var int
     */
    public int $ttl;

    /**
     * @var int
     */
    public int $maxJobs;

    /**
     * @var array
     */
    public array $channels;

    /**
     * @var string
     */
    public string $channelName;

    /**
     * @var bool
     */
    public bool $enabled;
}
