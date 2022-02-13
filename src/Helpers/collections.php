<?php

declare(strict_types=1);

namespace Chassis\Helpers;

use Closure;

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param mixed $args
     *
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('objectToArrayRecursive')) {
    function objectToArrayRecursive($data)
    {
        // Not an object or array
        if (!is_object($data) && !is_array($data)) {
            return $data;
        }

        // Parse array
        foreach ($data as $key => $value) {
            $arr[$key] = objectToArrayRecursive($value);
        }

        // Return parsed array
        return $arr ?? [];
    }
}
