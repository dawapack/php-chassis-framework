<?php

declare(strict_types=1);

namespace Chassis\Framework\OutboundAdapters\Cache;

use Chassis\Framework\OutboundAdapters\Cache\Connectors\ConnectorInterface;
use Predis\Client;
use Psr\Cache\CacheItemPoolInterface;
use Redis;

class RedisCache implements CachePoolImplementationInterface
{
    private ConnectorInterface $connector;
    private CacheItemPoolInterface $pool;
    /** @var Client|Redis $client */
    private $client;

    /**
     * @param ConnectorInterface $connector
     */
    public function __construct(ConnectorInterface $connector)
    {
        $this->connector = $connector;
        $this->client = $connector->client();
        $this->pool = $connector->getImplementation();
    }

    /**
     * @return Client|Redis
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function keyPrefix(): string
    {
        return $this->connector->getKeyPrefix();
    }

    /**
     * @inheritdoc
     */
    public function pool(): CacheItemPoolInterface
    {
        return $this->pool;
    }
}