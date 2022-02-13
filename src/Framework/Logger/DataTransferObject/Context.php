<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class Context extends DataTransferObject
{
    /* @var \Chassis\Framework\Logger\DataTransferObject\ContextError */
    public ?ContextError $error;
}
