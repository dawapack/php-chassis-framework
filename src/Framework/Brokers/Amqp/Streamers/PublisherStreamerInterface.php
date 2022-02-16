<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Streamers;

use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\Handlers\AckNackHandlerInterface;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelClosedException;

interface PublisherStreamerInterface
{
    /**
     * @return AckNackHandlerInterface
     */
    public function getAckHandler(): AckNackHandlerInterface;

    /**
     * @param AckNackHandlerInterface $ackHandler
     *
     * @return PublisherStreamerInterface
     */
    public function setAckHandler(AckNackHandlerInterface $ackHandler): PublisherStreamerInterface;

    /**
     * @return AckNackHandlerInterface
     */
    public function getNackHandler(): AckNackHandlerInterface;

    /**
     * @param AckNackHandlerInterface $nackHandler
     *
     * @return PublisherStreamerInterface
     */
    public function setNackHandler(AckNackHandlerInterface $nackHandler): PublisherStreamerInterface;

    /**
     * @param MessageBagInterface $messageBag
     * @param string $channelName
     * @param int|float $publishAcknowledgeTimeout
     *
     * @return void
     * @throws StreamerChannelClosedException
     */
    public function publish(
        MessageBagInterface $messageBag,
        string $channelName = "",
        $publishAcknowledgeTimeout = 5
    ): void;
}
