<?php

declare(strict_types=1);

namespace Chassis\Framework\OutboundAdapters\Cache;

use Chassis\Framework\OutboundAdapters\Cache\Connectors\RedisConnector;
use Chassis\Framework\OutboundAdapters\Cache\Exceptions\CachePoolImplementationException;
use Chassis\Framework\OutboundAdapters\Cache\Exceptions\ServerConnectionException;

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

        var_dump([__METHOD__, $this->cacheConfiguration]);
    }

    /**
     * @return CachePoolImplementationInterface
     *
     * @throws CachePoolImplementationException
     * @throws ServerConnectionException
     */
    public function get(): CachePoolImplementationInterface
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