<?php

declare(strict_types=1);

namespace Chassis\Helpers;

use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\Handlers\MessageHandlerInterface;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\InfrastructureStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamerInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\SubscriberStreamerInterface;
use Chassis\Framework\Brokers\Exceptions\MessageBagFormatException;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelClosedException;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelNameNotFoundException;
use Closure;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
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
     * @throws MessageBagFormatException
     * @throws JsonException
     */
    function remoteProcedureCall(
        BrokerRequest $message,
        int $timeout = 30
    ): ?BrokerResponse {
        $activeRpc = (new InfrastructureStreamer(app()))->brokerActiveRpcSetup();
        $message->setReplyTo($activeRpc["name"]);

        // publish the message
        publish($message);

        // basic get message - wait a new message or timeout
        $until = time() + $timeout;
        do {
            $response = $activeRpc["channel"]->basic_get($activeRpc["name"]);
            // wait a while - prevent CPU load
            usleep(10000);
        } while ($until > time() && is_null($response));

        // handle response
        if ($response instanceof AMQPMessage) {
            // ack the message
            $response->ack();
            return new BrokerResponse(
                $response->getBody(),
                $response->get_properties(),
                $response->getConsumerTag()
            );
        }

        return null;
    }
}