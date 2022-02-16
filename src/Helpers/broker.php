<?php

declare(strict_types=1);

namespace Chassis\Helpers;

use Chassis\Framework\Brokers\Amqp\Contracts\ContractsManagerInterface;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamer;
use Closure;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\Handlers\MessageHandlerInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamerInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamerInterface;

if (!function_exists('publish')) {
    /**
     * @param MessageBagInterface $messageBag
     * @param string|null $channelName
     *
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    function publish(
        MessageBagInterface $messageBag,
        string $channelName = "",
        int $publishAcknowledgeTimeout = 5
    ): void {
        /** @var PublisherStreamer $publisher */
        $publisher = app(PublisherStreamerInterface::class);
        $publisher->publish($messageBag, $channelName, $publishAcknowledgeTimeout);
    }
}

if (!function_exists('subscribe')) {
    /**
     * @param string $channelName
     * @param string $messageBagHandler - BrokerRequest::class or BrokerResponse::class
     * @param Closure|MessageHandlerInterface|null $messageHandler
     *
     * @return SubscriberStreamer
     */
    function subscribe(string $channelName, string $messageBagHandler, $messageHandler = null): SubscriberStreamer
    {
        /** @var SubscriberStreamer $subscriber */
        $subscriber = app(SubscriberStreamerInterface::class);
        return $subscriber->setChannelName($channelName)
            ->setHandler($messageBagHandler)
            ->consume($messageHandler);
    }
}
