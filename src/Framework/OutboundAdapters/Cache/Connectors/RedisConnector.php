<?php

declare(strict_types=1);

namespace Chassis\Framework\OutboundAdapters\Cache\Connectors;

use Cache\Adapter\Predis\PredisCachePool;
use Cache\Adapter\Redis\RedisCachePool;
use Chassis\Framework\OutboundAdapters\Cache\Exceptions\ServerConnectionException;
use Predis\Client;
use Psr\Cache\CacheItemPoolInterface;
use Redis;

class RedisConnector implements ConnectorInterface
{
    public const KEY_SEPARATOR = ".";
    private const DEFAULT_DRIVER = "redis";
    private const DEFAULT_SERVER = "tcp://localhost:5672";
    private const DEFAULT_DATABASE = 0;
    private const DEFAULT_TIMEOUT = 5.0;
    private const DEFAULT_RETRY_INTERVAL = 5;
    private const DEFAULT_READ_TIMEOUT = 1.0;

    /** @var Redis|Client */
    private $client;
    private array $configuration;
    private array $implementations = [
        'redis' => RedisCachePool::class,
        'predis' => PredisCachePool::class,
    ];
    private string $prefix;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->setPrefix();
    }

    /**
     * @inheritdoc
     */
    public function getKeyPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @inheritdoc
     */
    public function getImplementation(): CacheItemPoolInterface
    {
        !isset($this->client) && $this->connect();
        $implementation = $this->implementations[$this->getDriver()];
        return new $implementation($this->client());
    }

    /**
     * @inheritdoc
     */
    public function client()
    {
        !isset($this->client) && $this->connect();
        return $this->client;
    }

    /**
     * @return void
     */
    protected function setPrefix(): void
    {
        $this->prefix = $this->configuration["options"]["prefix"] ?? "";
        if (!empty($this->prefix) && substr($this->prefix, -1) !== self::KEY_SEPARATOR) {
            $this->prefix .= self::KEY_SEPARATOR;
        }
    }

    /**
     * @return array
     */
    protected function getServers(): array
    {
        return explode(",", $this->configuration["servers"] ?? [self::DEFAULT_SERVER]);
    }

    /**
     * @return int
     */
    protected function getDatabase(): int
    {
        return $this->configuration["database"] ?? self::DEFAULT_DATABASE;
    }

    /**
     * Connection timeout
     *
     * @return float
     */
    protected function getTimeout(): float
    {
        return $this->configuration["connection"]["timeout"] ?? self::DEFAULT_TIMEOUT;
    }

    /**
     * On connection fail, retry after X seconds
     *
     * @return int
     */
    protected function getRetryInterval(): int
    {
        return $this->configuration["connection"]["retryInterval"] ?? self::DEFAULT_RETRY_INTERVAL;
    }

    /**
     * @return float
     */
    protected function getReadTimeout(): float
    {
        return $this->configuration["connection"]["readTimeout"] ?? self::DEFAULT_READ_TIMEOUT;
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return $this->configuration["options"] ?? [];
    }

    /**
     * @return void
     *
     * @throws ServerConnectionException
     */
    protected function connect(): void
    {
        // remove prefix from options - mandatory
        unset($this->configuration["options"]["prefix"]);

        if (strtolower($this->getDriver()) === "redis") {
            $this->client = new Redis();
            if (!$this->client->connect(...$this->toRedisClientConnectArguments())) {
                throw new ServerConnectionException("unable to connect to the cache server");
            }
            return;
        }

        $this->client = new Client(...$this->toPredisClientConnectArguments());
    }

    /**
     * @return string
     */
    protected function getDriver(): string
    {
        return $this->configuration["driver"] ?? self::DEFAULT_DRIVER;
    }

    /**
     * At this time the connector do not handle RedisArray, Cluster or Sentinel
     *
     * @return array
     */
    protected function toRedisClientConnectArguments(): array
    {
        $server = parse_url($this->getServers()[0]);
        return [
            $server["host"],
            $server["port"],
            $this->getTimeout(),
            null,
            $this->getRetryInterval(),
            $this->getReadTimeout(),
        ];
    }

    /**
     * At this time the connector do not handle replications, aggregate connections, etc...
     *
     * @return array
     */
    protected function toPredisClientConnectArguments(): array
    {
        $server = parse_url($this->getServers()[0]);
        return [
            [
                'scheme' => $server["scheme"],
                'host' => $server["host"],
                'port' => $server["port"],
            ],
            array_merge(
                $this->getOptions(),
                [
                    'timeout' => $this->getTimeout(),
                    'retry_interval' => $this->getRetryInterval(),
                    'read_write_timeout' => $this->getReadTimeout(),
                ]
            )
        ];
    }
}
