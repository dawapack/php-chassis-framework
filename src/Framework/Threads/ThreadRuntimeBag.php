<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads;

class ThreadRuntimeBag
{
    /**
     * @var ThreadRuntimeBag $instance
     */
    private static ThreadRuntimeBag $instance;

    /**
     * @param ThreadRuntimeBag|null $fromInstance
     *
     * @return ThreadRuntimeBag
     */
    public static function factory(ThreadRuntimeBag $fromInstance = null): ThreadRuntimeBag
    {
        // clone an instance of ThreadRuntimeBag
        if (!is_null($fromInstance)) {
            return self::$instance = $fromInstance;
        }
        // return an existing or create a new ThreadRuntimeBag instance
        return self::$instance ?? self::$instance = new ThreadRuntimeBag();
    }

    /**
     * @param string $name
     * @param $value
     *
     * @return $this
     */
    public function with(string $name, $value): ThreadRuntimeBag
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
