<?php

declare(strict_types=1);

namespace Chassis\Framework\OutboundAdapters\Cache;

use Chassis\Framework\OutboundAdapters\Cache\Exceptions\CachePoolImplementationException;
use Chassis\Framework\OutboundAdapters\Cache\Exceptions\ServerConnectionException;

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
