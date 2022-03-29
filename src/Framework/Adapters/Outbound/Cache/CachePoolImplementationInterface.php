<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Outbound\Cache;

use Psr\Cache\CacheItemPoolInterface;

interface CachePoolImplementationInterface
{
    /**
     * @return mixed
     */
    public function client();

    /**
     * @return string
     */
    public function keyPrefix(): string;

    /**
     * @return CacheItemPoolInterface
     */
    public function pool(): CacheItemPoolInterface;
}
