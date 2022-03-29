<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Outbound\Cache\Connectors;

use Psr\Cache\CacheItemPoolInterface;

interface ConnectorInterface
{
    /**
     * @return mixed
     */
    public function client();

    /**
     * @return string
     */
    public function getKeyPrefix(): string;

    /**
     * @return CacheItemPoolInterface
     */
    public function getImplementation(): CacheItemPoolInterface;
}
