<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject;

use Chassis\Framework\Brokers\Amqp\Configurations\BindingsInterface;
use Spatie\DataTransferObject\DataTransferObject;

class OperationBindings extends DataTransferObject implements BindingsInterface
{
    public ?int $expiration;
    public ?string $userId;
    public ?array $cc = [];
    public ?int $priority;
    public ?int $deliveryMode;
    public ?bool $mandatory;
    public ?array $bcc = [];
    public ?string $replyTo;
    public ?bool $timestamp;
    public ?bool $ack;

    /**
     * @inheritDoc
     */
    public function toFunctionArguments(bool $onlyValues = true): array
    {
        return $this->toArray();
    }
}
