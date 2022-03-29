<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Setup;

use Chassis\Framework\Bus\SetupBusInterface;

interface AMQPSetupInterface extends SetupBusInterface
{
    /**
     * Create all channels given by the async contract
     *
     * @param bool $passive
     *
     * @return void
     */
    public function setup(bool $passive = true): void;

    /**
     * Remove all jobs from a given channel (queue)
     *
     * @param string $channel
     *
     * @return bool
     */
    public function purge(string $channel): bool;

    /**
     * @param array $options
     *
     * @return string|null
     */
    public function setupCallbackQueue(array $options = []): ?string;
}
