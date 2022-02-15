<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;
use Throwable;

class ContextError extends DataTransferObject
{
    public ?string $file = null;
    public int $line = 0;
    public ?string $message;
    public ?string $trace;

    /**
     * Fill DTO properties from throwable
     *
     * @param Throwable $throwable
     *
     * @return ContextError
     */
    public function fillFromThrowable(Throwable $throwable): ContextError
    {
        $this->message = $throwable->getMessage();
        $this->file = $throwable->getFile();
        $this->line = $throwable->getLine();
        $this->trace = $throwable->getTraceAsString();

        return $this;
    }
}
