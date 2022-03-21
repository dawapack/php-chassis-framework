<?php

declare(strict_types=1);

namespace Chassis\Framework;

class RuntimeBag
{
    private static RuntimeBag $instance;

    public static function factory(RuntimeBag $fromInstance = null): RuntimeBag
    {
        // clone an instance of BootstrapBag
        if (!is_null($fromInstance)) {
            return self::$instance = $fromInstance;
        }
        // return an existing or create a new BootstrapBag instance
        return self::$instance ?? self::$instance = new RuntimeBag();
    }

    /**
     * @param string $name
     * @param $value
     *
     * @return $this
     */
    public function with(string $name, $value): RuntimeBag
    {
        $this->{$name} = $value;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->{$name} ?? null;
    }
}
