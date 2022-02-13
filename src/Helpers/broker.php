<?php

declare(strict_types=1);

namespace Chassis\Helpers;

use Closure;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\Handlers\MessageHandlerInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamerInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamerInterface;

if (!function_exists('publish')) {
    /**
     * @param BrokerRequest|BrokerResponse $data
     * @param string|null $channelName
     * @param BrokerRequest|null $context
     *
     * @return void
     */
    function publish($data, ?string $channelName = null, ?BrokerRequest $context = null): void
    {
        // set routing key from given context
        if (($context instanceof BrokerRequest) && !is_null($context->getProperty('reply_to'))) {
            $data->setRoutingKey($context->getProperty('reply_to'));
        }
        app(PublisherStreamerInterface::class)
            ->publish($data, $channelName);
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
