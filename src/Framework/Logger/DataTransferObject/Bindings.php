<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class Bindings extends DataTransferObject
{
    public array $channelBindings = [];
    public array $operationBindings = [];
    public array $messageBindings = [];
}
