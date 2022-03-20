<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Inbound;

use Chassis\Framework\Brokers\Exceptions\StreamerChannelNameNotFoundException;

interface InboundBusAdapterInterface
{
    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options): self;

    /**
     * @param string $channel
     *
     * @return void
     *
     * @throws StreamerChannelNameNotFoundException
     */
    public function subscribe(string $channel): void;

    /**
     * @return void
     */
    public function pool(): void;

    /**
     * @param string $channel
     * @param string|null $correlationId
     * @param int|null $timeout
     *
     * @return mixed
     */
    public function get(string $channel, string $correlationId = null, int $timeout = null);
}
