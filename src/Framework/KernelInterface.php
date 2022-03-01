<?php

declare(strict_types=1);

namespace Chassis\Framework;

interface KernelInterface
{
    /**
     * @return void
     */
    public function boot(): void;
}
