<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads\Configuration;

interface ThreadsConfigurationInterface
{
    /**
     * Is active infrastructure thread
     *
     * @return bool
     */
    public function hasInfrastructureThread(): bool;

    /**
     * Is active centralized configuration thread
     *
     * @return bool
     */
    public function hasCentralizedConfigurationThread(): bool;

    /**
     * @return bool
     */
    public function hasWorkerThreads(): bool;

    /**
     * Return properties will be used to build worker, infrastructure, or configuration threads
     *
     * @param string $threadType
     *
     * @return ThreadConfiguration
     */
    public function getThreadConfiguration(string $threadType): ThreadConfiguration;
}
