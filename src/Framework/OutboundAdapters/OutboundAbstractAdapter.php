<?php

declare(strict_types=1);

namespace Chassis\Framework\OutboundAdapters;

use Chassis\Application;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\InfrastructureStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use function Chassis\Helpers\app;

class OutboundAbstractAdapter implements BrokerOutboundAdapterInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "outbound_adapter_";

    protected Application $application;
    protected string $channelName;
    protected string $routingKey;
    protected string $replyTo;
    protected bool $isSyncOverAsync = false;

    public function __construct()
    {
        $this->application = app();
    }

    /**
     * Nobody cares about the implementation
     *
     * @param MessageBagInterface $message
     *
     * @return BrokerResponse|null
     */
    public function __invoke(MessageBagInterface $message): ?BrokerResponse
    {
        return $this->send($message);
    }

    /**
     * @inheritdoc
     */
    public function send(MessageBagInterface $message, int $timeout = 30): ?BrokerResponse
    {
        if ($this->isSyncOverAsync) {
            $this->channelName = "";
            $this->replyTo = $this->createCallbackQueue();
        }
        // alter message if message is request type
        if ($message instanceof BrokerRequest) {
            $message->setChannelName($this->channelName ?? "");
            $message->setRoutingKey($this->routingKey ?? "");
            $message->setReplyTo($this->replyTo ?? "");
        }

        /** @var PublisherStreamer $publisher */
        $publisher = $this->application->get(PublisherStreamerInterface::class);
        $publisher->publish($message, $this->channelName);

        if ($this->isSyncOverAsync) {
            return $this->pull($timeout);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function pull(int $timeout = 30): ?BrokerResponse
    {
        /** @var AMQPChannel $channel */
        $channel = ($this->application->get("brokerStreamConnection"))->channel();
        try {
            // basic get message - wait a new message or timeout
            $until = time() + $timeout;
            do {
                $response = $channel->basic_get($this->replyTo);
                if (!is_null($response)) {
                    break;
                }
                // wait a while - prevent CPU load
                usleep(5000);
            } while ($until > time());

            // handle response
            if ($response instanceof AMQPMessage) {
                // ack the message
                $response->ack();
                // close channel
                $channel->close();
                // return message
                return new BrokerResponse(
                    $response->getBody(),
                    $response->get_properties(),
                    "no_tag_for_basic_get"
                );
            }
        } catch (Throwable $reason) {
            $this->application->logger()->error(
                $reason->getMessage(),
                [
                    "component" => "remote_procedure_call_function_exception",
                    "error" => $reason
                ]
            );
        }
        // no message retrieved || exception thrown - close the channel
        $channel->close();

        return null;
    }

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function createCallbackQueue(): string
    {
        return (new InfrastructureStreamer($this->application))
            ->brokerActiveRpcSetup();
    }
}
