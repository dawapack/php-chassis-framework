<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads\Configuration;

class ThreadsConfiguration implements ThreadsConfigurationInterface
{
    private array $configuration;

    /**
     * ThreadsConfiguration constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @inheritDoc
     */
    public function getThreadMaximumJobsBeforeRespawn(): int
    {
        return $this->configuration["max_jobs"];
    }

    /**
     * @inheritDoc
     */
    public function getMinimumVerticalScalingCount(): int
    {
        return $this->configuration["minimum"];
    }

    /**
     * @inheritDoc
     */
    public function getMaximumVerticalScalingCount(): int
    {
        return $this->configuration["maximum"];
    }

    /**
     * @inheritDoc
     */
    public function getVerticalScalingTriggers(): int
    {
        return $this->configuration["triggers"];
    }

    /**
     * @inheritDoc
     */
    public function getThreadTimeToLive(): int
    {
        return $this->configuration["ttl"];
    }

    /**
     * @inheritDoc
     */
    public function hasInfrastructureThread(): bool
    {
        return $this->configuration["infrastructure"]["enabled"];
    }

    /**
     * @inheritDoc
     */
    public function hasCentralizedConfigurationThread(): bool
    {
        return $this->configuration["configuration"]["enabled"];
    }

    /**
     * @inheritDoc
     */
    public function hasWorkerThreads(): bool
    {
        return $this->configuration["worker"]["enabled"];
    }

    /**
     * @param string $threadType
     *
     * @return ThreadConfiguration
     */
    public function getThreadConfiguration(string $threadType): ThreadConfiguration
    {
        $threadConfiguration = $this->configuration;
        // map key
        $threadConfiguration["maxJobs"] = $threadConfiguration["max_jobs"];
        $threadConfiguration["threadType"] = $threadType;
        $threadConfiguration["channelName"] = $threadType;

        $threadConfiguration = array_merge(
            array_diff_key($threadConfiguration, $threadConfiguration[$threadType]),
            $threadConfiguration[$threadType]
        );
        // infrastructure & centralized configuration haven't triggers & channels
        if ($threadType !== "worker") {
            $threadConfiguration["triggers"] = [];
            $threadConfiguration["channels"] = [];
        } else {
            $channels = [];
            foreach ($threadConfiguration["channels"] as $channelName => $channel) {
                $channel = array_merge(
                    array_diff_key($threadConfiguration, $channel),
                    $channel
                );
                // map key
                $channel["maxJobs"] = $channel["max_jobs"];
                $channel["channelName"] = $channelName;
                // channels property must be empty
                $channel["channels"] = [];
                $this->cleanUnhandledProperties($channel);
                $channels[$channelName] = $channel;
            }
            $threadConfiguration["channels"] = $channels;
        }
        $this->cleanUnhandledProperties($threadConfiguration);

        return new ThreadConfiguration($threadConfiguration);
    }

    /**
     * @param array $threadConfiguration
     *
     * @return void
     */
    private function cleanUnhandledProperties(array &$threadConfiguration): void
    {
        $threadConfiguration = array_diff_key(
            $threadConfiguration,
            array_flip(["infrastructure", "configuration", "worker", "max_jobs"])
        );
    }
}
