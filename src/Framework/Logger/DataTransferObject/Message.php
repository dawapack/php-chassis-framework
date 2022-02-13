<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class Message extends DataTransferObject
{
    public array $properties = [];
    public array $headers = [];
    public ?string $body = null;
}
