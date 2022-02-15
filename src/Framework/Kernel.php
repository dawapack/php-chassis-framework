<?php

declare(strict_types=1);

namespace Chassis\Framework;

use Chassis\Application;
use Chassis\Framework\Threads\ThreadsManagerInterface;
use Chassis\Framework\Workers\WorkerInterface;
use Chassis\Helpers\Pcntl\PcntlSignals;
use Psr\Log\LoggerInterface;

class Kernel implements KernelInterface
{
    private Application $app;
    private string $loggerComponent = "kernel_" . RUNNER_TYPE;
    private bool $stopRequested = false;

    /**
     * Kernel constructor.
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->app = $application;
        $this->bootstrapSignals();
    }

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


    /**
     * @inheritDoc
     */
    public function app(): Application
    {
        return $this->app;
    }

    /**
     * @inheritDoc
     */
    public function logger(): LoggerInterface
    {
        return $this->app->logger();
    }

    /**
     * @return void
     */
    protected function bootstrapSignals(): void
    {
        pcntl_signal(PcntlSignals::SIGTERM, array($this, 'signalHandler'));
    }
}
