<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus;

interface InboundBusInterface
{
    /**
     * @param string $channel
     * @param array $options
     * @param array $qos
     *
     * @return void
     */
    public function consume(
        string $channel,
        array $options = [],
        array $qos = []
    ): void;

    /**
     * @return void
     */
    public function iterate(): void;

    /**
     * @param string $queueName
     * @param string|null $correlationId
     * @param int $timeout
     *
     * @return mixed
     */
    public function get(string $queueName, ?string $correlationId = null, int $timeout = 30);
}
