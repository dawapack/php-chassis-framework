<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads\DataTransferObject;

use DateTimeImmutable;
use Spatie\DataTransferObject\DataTransferObject;

class JobStatistics extends DataTransferObject
{
    /**
     * @var string
     */
    public string $jobId;

    /**
     * @var string
     */
    public string $messageId;

    /**
     * @var string
     */
    public string $messageType;

    /**
     * @var DateTimeImmutable
     */
    public DateTimeImmutable $dateTimeStart;

    /**
     * @var DateTimeImmutable
     */
    public DateTimeImmutable $dateTimeEnd;
}
