<?php

declare(strict_types=1);

namespace Chassis\Framework;

use Psr\Log\LoggerInterface;

interface KernelInterface
{
    /**
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface;

    /**
     * @return void
     */
    public function boot(): void;
}
