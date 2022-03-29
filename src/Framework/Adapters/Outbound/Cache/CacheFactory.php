<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Outbound\Cache;

use Chassis\Framework\Adapters\Outbound\Cache\Connectors\RedisConnector;
use Chassis\Framework\Adapters\Outbound\Cache\Exceptions\CachePoolImplementationException;

class CacheFactory implements CacheFactoryInterface
{
    private const REDIS_STORE = "redis";

    private array $cacheConfiguration;

    /**
     * @param array $cacheConfiguration
     */
    public function __construct(array $cacheConfiguration)
    {
        $this->cacheConfiguration = $cacheConfiguration;
    }

    /**
     * @return CachePoolImplementationInterface
     *
     * @throws CachePoolImplementationException
     */
    public function build(): CachePoolImplementationInterface
    {
        if ($this->cacheConfiguration["default"] === self::REDIS_STORE) {
            return new RedisCache(
                new RedisConnector(
                    $this->cacheConfiguration["stores"][self::REDIS_STORE]
                )
            );
        }
        throw new CachePoolImplementationException(
            sprintf("unknown '%s' cache implementation", $this->cacheConfiguration["default"])
        );
    }
}
