<?php

declare(strict_types=1);

namespace Chassis\Helpers;

use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\Handlers\MessageHandler;
use Chassis\Framework\Brokers\Amqp\Handlers\MessageHandlerInterface;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamerInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamerInterface;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelClosedException;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelNameNotFoundException;
use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (!function_exists('publish')) {
    /**
     * @param MessageBagInterface $messageBag
     * @param string|null $channelName
     * @param int $publishAcknowledgeTimeout
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws StreamerChannelClosedException
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
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws StreamerChannelNameNotFoundException
     */
    function subscribe(
        string $channelName,
        string $messageBagHandler,
        $messageHandler = null
    ): SubscriberStreamer {
        /** @var SubscriberStreamer $subscriber */
        $subscriber = app(SubscriberStreamerInterface::class);
        return $subscriber->setChannelName($channelName)
            ->setHandler($messageBagHandler)
            ->consume($messageHandler);
    }
}

if (!function_exists('remoteProcedureCall')) {
    /**
     * @param BrokerRequest $message
     * @param int $timeout
     *
     * @return BrokerResponse|null
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws StreamerChannelClosedException
     * @throws StreamerChannelNameNotFoundException
     */
    function remoteProcedureCall(
        BrokerRequest $message,
        int $timeout = 30
    ): ?BrokerResponse {
        /** @var MessageHandler $messageHandler */
        $messageHandler = (app(MessageHandlerInterface::class))->clear();
        $subscriber = subscribe("", BrokerResponse::class, $messageHandler);
        $message->setReplyTo($subscriber->getQueueName());

        // publish
        publish($message);

        // iterate consumer
        $until = time() + $timeout;
        do {
            // wait a while - prevent CPU load
            usleep(10000);
            $subscriber->iterate();
            // wait a while - prevent CPU load
            usleep(40000);
        } while ($until > time() && is_null($messageHandler->getMessage()));

        return $messageHandler->getMessage();
    }
}