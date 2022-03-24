<?php

declare(strict_types=1);

namespace Chassis\Framework;

use Chassis\Application;
use Chassis\Framework\Threads\ThreadsManagerInterface;
use Chassis\Framework\Workers\WorkerInterface;
use Chassis\Helpers\Pcntl\PcntlSignals;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class Kernel implements KernelInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "kernel_";

    private Application $app;
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
        try {

            if ($this->app->isWorker()) {
                ($this->app->get(WorkerInterface::class))->start();
            } elseif ($this->app->isDaemon()) {
                ($this->app->get(ThreadsManagerInterface::class))->start($this->stopRequested);
            }

        } catch (\Throwable $reason) {
            var_dump($reason);
        }
    }

    /**
     * @param int $signalNumber
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function signalHandler(int $signalNumber): void
    {
        $this->stopRequested = true;
        if ($signalNumber === PcntlSignals::SIGTERM) {
            return;
        }
        $this->app->logger()->alert(
            "exit on unhandled signal",
            [
                "component" => self::LOGGER_COMPONENT_PREFIX . RUNNER_TYPE,
                "signal" => PcntlSignals::$toSignalName[$signalNumber]
            ]
        );
    }

    /**
     * @return void
     */
    protected function bootstrapSignals(): void
    {
        pcntl_signal(PcntlSignals::SIGTERM, array($this, 'signalHandler'));
    }
}
