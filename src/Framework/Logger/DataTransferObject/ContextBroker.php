<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class ContextBroker extends DataTransferObject
{
    public string $channelName;

    /* @var \Chassis\Framework\Logger\DataTransferObject\Bindings */
    public Bindings $bindings;

    /* @var \Chassis\Framework\Logger\DataTransferObject\Message */
    public Message $message;
}
