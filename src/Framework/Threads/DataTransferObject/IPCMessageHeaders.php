<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class IPCMessageHeaders extends DataTransferObject
{
    /**
     * @var string|null
     */
    public ?string $method = null;

    /**
     * @var string|null
     */
    public ?string $source = null;

    /**
     * @var string|null
     */
    public string $encoding = 'array';
}
