<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads\Configuration;

interface ThreadsConfigurationInterface
{
    /**
     * Return the limit of maximum jobs before respawn thread
     *
     * @return int
     */
    public function getThreadMaximumJobsBeforeRespawn(): int;

    /**
     * Return the limit of minimum scaling threads
     *
     * @return int
     */
    public function getMinimumVerticalScalingCount(): int;

    /**
     * Return the limit of maximum scaling threads
     *
     * @return int
     */
    public function getMaximumVerticalScalingCount(): int;

    /**
     * Return the list of vertical scaling triggers
     *
     * @return int
     */
    public function getVerticalScalingTriggers(): int;

    /**
     * Return the thread time to live in seconds
     *
     * @return int
     */
    public function getThreadTimeToLive(): int;

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
     * Return the settings of worker, infrastructure, or configuration thread type
     *
     * @param string $threadType
     *
     * @return ThreadConfiguration
     */
    public function getThreadConfiguration(string $threadType): ThreadConfiguration;
}
