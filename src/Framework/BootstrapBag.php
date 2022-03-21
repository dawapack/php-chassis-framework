<?php

declare(strict_types=1);

namespace Chassis\Framework;

class BootstrapBag
{
    private static BootstrapBag $instance;

    public static function factory(BootstrapBag $fromInstance = null): BootstrapBag
    {
        // clone an instance of BootstrapBag
        if (!is_null($fromInstance)) {
            return self::$instance = $fromInstance;
        }
        // return an existing or create a new BootstrapBag instance
        return self::$instance ?? self::$instance = new BootstrapBag();
    }

    /**
     * @param string $name
     * @param $value
     *
     * @return $this
     */
    public function with(string $name, $value): BootstrapBag
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
