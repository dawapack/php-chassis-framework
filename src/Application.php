<?php

declare(strict_types=1);

namespace Chassis;

use Chassis\Concerns\Bootstraps;
use Chassis\Concerns\ErrorsHandler;
use Chassis\Concerns\Runner;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Application extends Container
{
    use Bootstraps;
    use ErrorsHandler;
    use Runner;

    private static Application $instance;
    private LoggerInterface $logger;
    private string $basePath;
    private array $properties;

    /**
     * Application constructor.
     *
     * @param string|null $basePath
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function __construct(string $basePath = null)
    {
        parent::__construct();
        $this->basePath = $basePath;

        $this->defaultToShared(true);
        $this->enableAutoWiring();
        $this->bootstrapContainer();
        $this->registerErrorHandling();
        $this->registerRunnerType();

        if (!$this->runningInConsole()) {
            trigger_error("Run only in cli mode", E_USER_ERROR);
        }

        // final bootstraps
        if ($this->isDaemon()) {
            $this->bootstrapDaemon();
        } elseif ($this->isWorker()) {
            $this->bootstrapWorker();
        }

        self::$instance = $this;
    }

    /**
     * @return Application|null
     */
    public static function getInstance(): ?Application
    {
        return isset(self::$instance) && (self::$instance instanceof Application)
            ? self::$instance
            : null;
    }

    /**
     * @return bool
     */
    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * @param string $key
     *
     * @return array|mixed|null
     *
     * @throws Throwable
     */
    public function config(string $key)
    {
        try {
            return $this->get('config')->get($key);
        } catch (Throwable $reason) {
            $this->logger()->error(
                $reason->getMessage(),
                [
                    "component" => "application_configuration_get_exception",
                    "error" => $reason
                ]
            );
        }
        return null;
    }

    /**
     * @param string $alias
     *
     * @return void
     *
     * @throws Throwable
     */
    public function withConfig(string $alias): void
    {
        try {
            $this->get('config')->load($alias);
        } catch (Throwable $reason) {
            $this->logger()->error(
                $reason->getMessage(),
                [
                    "component" => "application_with_configuration_exception",
                    "error" => $reason
                ]
            );
        }
    }

    /**
     * @return LoggerInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function logger(): LoggerInterface
    {
        if (isset($this->logger)) {
            return $this->logger;
        }
        $this->logger = $this->get(LoggerInterface::class);
        return $this->logger;
    }

    /**
     * Instantiate the container auto wiring - resolutions will be cached
     *
     * @return void
     */
    private function enableAutoWiring(): void
    {
        $this->delegate(new ReflectionContainer(true));
    }
}
