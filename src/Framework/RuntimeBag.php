<?php

declare(strict_types=1);

namespace Chassis\Framework;

class RuntimeBag
{
    /**
     * @var RuntimeBag $instance
     */
    private static RuntimeBag $instance;

    /**
     * @param RuntimeBag|null $fromInstance
     *
     * @return RuntimeBag
     */
    public static function factory(RuntimeBag $fromInstance = null): RuntimeBag
    {
        // clone an instance of RuntimeBag
        if (!is_null($fromInstance)) {
            return self::$instance = $fromInstance;
        }
        // return an existing or create a new RuntimeBag instance
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
     * This is only a bag, so we use magic method get to access properties
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->{$name} ?? null;
    }
}
