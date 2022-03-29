<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Outbound\Cache;

use Chassis\Framework\Adapters\Outbound\Cache\Exceptions\CachePoolImplementationException;
use Chassis\Framework\Adapters\Outbound\Cache\Exceptions\ServerConnectionException;

interface CacheFactoryInterface
{
    /**
     * @return CachePoolImplementationInterface
     *
     * @throws CachePoolImplementationException
     * @throws ServerConnectionException
     */
    public function build(): CachePoolImplementationInterface;
}
