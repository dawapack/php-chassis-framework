<?php

declare(strict_types=1);

namespace Chassis\Framework\Configuration;

interface ConfigurationInterface
{
    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function get(string $key, $default = null);

    /**
     * @param array|string $alias
     */
    public function load($alias): void;
}
