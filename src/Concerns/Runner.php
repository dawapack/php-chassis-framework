<?php

declare(strict_types=1);

namespace Chassis\Concerns;

trait Runner
{
    private string $runnerType;

    /**
     * @return bool
     */
    public function isWorker(): bool
    {
        return RUNNER_TYPE === "worker";
    }

    /**
     * @return bool
     */
    public function isDaemon(): bool
    {
        return RUNNER_TYPE === "daemon";
    }

    /**
     * Check and register runner type
     *
     * @return void
     */
    private function registerRunnerType(): void
    {
        if (!defined('RUNNER_TYPE')) {
            define('RUNNER_TYPE', 'unknown');
            trigger_error(
                "boot script must define the runner type",
                E_USER_ERROR
            );
        }

        $this->runnerType = RUNNER_TYPE;
    }

    /**
     * @return bool
     */
    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }
}
