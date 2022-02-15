<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class Record extends DataTransferObject
{
    public string $timestamp;
    public string $origin;
    public string $region;

    /* @var \Chassis\Framework\Logger\DataTransferObject\Application */
    public Application $application;

    public string $level;
    public string $message = "";
    public string $component;

    /** @var \Chassis\Framework\Logger\DataTransferObject\Context */
    public Context $context;
    public ?string $extra = null;
}
