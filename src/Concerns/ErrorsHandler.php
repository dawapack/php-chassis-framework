<?php

declare(strict_types=1);

namespace Chassis\Concerns;

use Chassis\Exceptions\ApplicationErrorException;
use Chassis\Support\FatalError;
use Throwable;

use function Chassis\Helpers\env;

trait ErrorsHandler
{
    /**
     * Set the error handling for the application.
     *
     * @return void
     */
    protected function registerErrorHandling()
    {
        // skip custom errors handling in testing environment
        if (env('APP_ENV') === "testing") {
            return;
        }

        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            $this->handleError($level, $message, $file, $line);
        });

        set_exception_handler(function ($e) {
            $this->handleException($e);
        });

        register_shutdown_function(function () {
            $this->handleShutdown();
        });
    }

    /**
     * Convert PHP errors to ApplicationErrorException instances. Do not report deprecations!
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array $context
     *
     * @return void
     *
     * @throws ApplicationErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            // Do not throw deprecations
            if ($this->isDeprecation($level)) {
                return;
            }

            throw new ApplicationErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception instance.
     *
     * @param Throwable $reason
     *
     * @return void
     *
     * @throws Throwable
     */
    protected function handleException(Throwable $reason)
    {
        $this->logger()->alert(
            "Application unhandled exception",
            [
                "component" => "application_unhandled_exception",
                "error" => $reason
            ]
        );

        exit(1);
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     *
     * @throws Throwable
     */
    public function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalErrorFromPhpError($error, 0));
        }
    }

    /**
     * Create a new fatal error instance from an error array.
     *
     * @param array $error
     * @param int|null $traceOffset
     *
     * @return FatalError
     */
    protected function fatalErrorFromPhpError(array $error, $traceOffset = null)
    {
        return new FatalError($error['message'], 0, $error, $traceOffset);
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param int $type
     *
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * Determine if the error level is a deprecation.
     *
     * @param int $level
     *
     * @return bool
     */
    protected function isDeprecation($level)
    {
        return in_array($level, [E_DEPRECATED, E_USER_DEPRECATED]);
    }
}
