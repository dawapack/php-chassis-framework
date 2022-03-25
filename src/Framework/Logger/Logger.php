<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use function Chassis\Helpers\app;

class Logger
{
    public static function emergency(string $message, array $context = [])
    {
        self::log("emergency", $message, $context);
    }

    public static function alert(string $message, array $context = [])
    {
        self::log("alert", $message, $context);
    }

    public static function critical(string $message, array $context = [])
    {
        self::log("critical", $message, $context);
    }

    public static function error(string $message, array $context = [])
    {
        self::log("error", $message, $context);
    }

    public static function warning(string $message, array $context = [])
    {
        self::log("warning", $message, $context);
    }

    public static function notice(string $message, array $context = [])
    {
        self::log("notice", $message, $context);
    }

    public static function info(string $message, array $context = [])
    {
        self::log("info", $message, $context);
    }

    public static function debug(string $message, array $context = [])
    {
        self::log("debug", $message, $context);
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        (app(LoggerInterface::class))->logger()->{$level}($message, $context);
    }
}