<?php

declare(strict_types=1);

namespace Chassis\Framework;

use Chassis\Framework\Threads\ThreadsManagerInterface;
use Chassis\Framework\Workers\WorkerInterface;
use Chassis\Helpers\Pcntl\PcntlSignals;

class Kernel extends KernelBase
{
    private bool $stopRequested = false;

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        switch (RUNNER_TYPE) {
            case "worker":
                ($this->app->get(WorkerInterface::class))->start();
                break;
            case "daemon":
                ($this->app->get(ThreadsManagerInterface::class))->start($this->stopRequested);
                break;
            case "cron":
                // TODO: implement cron worker type
                return;
        }
    }

    /**
     * @inheritDoc
     */
    protected function bootstrap(): void
    {
        $this->logger()->info(
            "kernel bootstrapped",
            ['component' => $this->loggerComponent]
        );
        $this->bootstrapSignals();
    }

    /**
     * @param int $signalNumber
     * @param $signalInfo
     */
    public function signalHandler(int $signalNumber, $signalInfo): void
    {
        $this->stopRequested = true;
        if ($signalNumber === PcntlSignals::SIGTERM) {
            return;
        }
        $this->logger()->alert(
            "exit on trapped signal",
            [
                "component" => $this->loggerComponent,
                "extra" => ["signal" => PcntlSignals::$toSignalName[$signalNumber]]
            ]
        );
    }
}
