<?php

declare(strict_types=1);

namespace Chassis\Framework;

use Chassis\Application;
use Psr\Log\LoggerInterface;

interface KernelInterface
{
    /**
     * @return Application
     */
    public function app(): Application;

    /**
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface;

    /**
     * @return void
     */
    public function boot(): void;
}
