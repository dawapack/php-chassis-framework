<?php

declare(strict_types=1);

namespace Chassis\Framework\Workers;

interface WorkerInterface
{
    /**
     * @return void
     */
    public function start(): void;
}
