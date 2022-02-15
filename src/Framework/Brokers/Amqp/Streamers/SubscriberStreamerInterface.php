<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Streamers;

use Closure;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelNameNotFoundException;

interface SubscriberStreamerInterface
{
    /**
     * Set message bag handler - BrokerRequest or BrokerResponse
     *
     * @param string $handler
     *
     * @return $this
     */
    public function setHandler(string $handler): self;

    /**
     * @return string|null
     */
    public function getHandler(): ?string;

    /**
     * @param int $qosPrefetchSize
     *
     * @return $this
     */
    public function setQosPrefetchSize(int $qosPrefetchSize): self;

    /**
     * @return int|null
     */
    public function getQosPrefetchSize(): ?int;

    /**
     * @param int $qosPrefetchCount
     *
     * @return $this
     */
    public function setQosPrefetchCount(int $qosPrefetchCount): self;

    /**
     * @return int|null
     */
    public function getQosPrefetchCount(): ?int;

    /**
     * @param bool $qosPerConsumer
     *
     * @return $this
     */
    public function setQosPerConsumer(bool $qosPerConsumer): self;

    /**
     * @return bool
     */
    public function isQosPerConsumer(): bool;

    /**
     * @return bool
     */
    public function consumed(): bool;

    /**
     * @return BrokerRequest|BrokerResponse|null
     */
    public function get();

    /**
     * @param Closure|null $callback
     *
     * @return $this
     *
     * @throws StreamerChannelNameNotFoundException
     */
    public function consume(?Closure $callback = null): self;

    /**
     * @return void
     */
    public function iterate(): void;
}
