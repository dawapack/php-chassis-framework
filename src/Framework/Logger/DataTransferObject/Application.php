<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class Application extends DataTransferObject
{
    public string $name;
    public string $environment;
    public string $type;
}
