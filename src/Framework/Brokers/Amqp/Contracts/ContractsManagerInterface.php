<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Contracts;

use Chassis\Framework\Brokers\Amqp\Handlers\MessageHandlerInterface;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Closure;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerChannel;
use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerChannelsCollection;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelNameNotFoundException;

interface ContractsManagerInterface
{
    /**
     * @param string $channelName
     *
     * @return BrokerChannel|null
     */
    public function getChannel(string $channelName): ?BrokerChannel;

    /**
     * @return BrokerChannelsCollection
     */
    public function getChannels(): BrokerChannelsCollection;

    /**
     * @param MessageBagInterface $message
     * @param string|null $channelName
     *
     * @return array
     *
     * @throws StreamerChannelNameNotFoundException
     */
    public function toBasicPublishFunctionArguments(MessageBagInterface $message, string $channelName): array;

    /**
     * @param string $channelName
     * @param Closure|MessageHandlerInterface $callback
     *
     * @return array
     *
     * @throws StreamerChannelNameNotFoundException
     */
    public function toBasicConsumeFunctionArguments(string $channelName, $callback): array;

    /**
     * @return array
     */
    public function toStreamConnectionFunctionArguments(): array;

    /**
     * @return array
     */
    public function toLazyConnectionFunctionArguments(): array;
}
