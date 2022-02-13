<?php

declare(strict_types=1);

namespace Chassis\Helpers;

use Chassis\Application;
use Chassis\Support\Env;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('app')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string|null $alias
     *
     * @return mixed|Application
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function app(?string $alias = null)
    {
        return is_null($alias)
            ? Application::getInstance()
            : Application::getInstance()->get($alias);
    }
}

if (!function_exists('defineRunner')) {
    /**
     * Declare runner from script options
     */
    function defineRunner()
    {
        if (defined('RUNNER_TYPE')) {
            return;
        }
        $options = getopt('', ['worker', 'daemon', 'channel::']);
        if (isset($options["daemon"])) {
            define('RUNNER_TYPE', 'daemon');
        } elseif (isset($options["worker"])) {
            define('RUNNER_TYPE', 'worker');
        }
        define('RUNNER_CHANNEL', $options["channel"] ?? null);
    }
}
