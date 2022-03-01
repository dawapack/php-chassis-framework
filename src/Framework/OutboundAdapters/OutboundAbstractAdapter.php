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
    protected string $operation;
    protected string $channelName;
    protected string $routingKey;
    protected string $replyTo;
    protected bool $isSyncOverAsync = false;

    /**
     * Nobody cares about the implementation
     *
     * @param MessageBagInterface $message
     * @param Application $application
     *
     * @return BrokerResponse|null
     */
    public function __invoke(MessageBagInterface $message, Application $application): ?BrokerResponse
    {
        $this->application = $application;
        return $this->push($message);
    }

    /**
     * @inheritdoc
     */
    public function push(MessageBagInterface $message, int $timeout = 30): ?BrokerResponse
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
        // alter message type
        if (isset($this->operation)) {
            $message->setMessageType($this->operation);
        }

        /** @var PublisherStreamer $publisher */
        $publisher = $this->application->get(PublisherStreamerInterface::class);
        $publisher->publish($message, $this->channelName);

        if ($this->isSyncOverAsync) {
            return $this->pull($timeout, $message);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function pull(int $timeout = 30, MessageBagInterface $context = null): ?BrokerResponse
    {
        /** @var AMQPChannel $channel */
        $channel = ($this->application->get("brokerStreamConnection"))->channel();
        try {
            // basic get message - wait a new message or timeout
            $until = time() + $timeout;
            do {
                $response = $channel->basic_get($this->replyTo);
                if (!is_null($response)) {
                    if ($this->isResponseForContext($response, $context)) {
                        break;
                    }
                    // remove this message from the queue
                    $response->nack();
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

    /**
     * @param AMQPMessage $response
     * @param MessageBagInterface|null $context
     *
     * @return bool
     */
    protected function isResponseForContext(AMQPMessage $response, MessageBagInterface $context = null): bool
    {
        // if no context is provided, this will always return true
        if (is_null($context)) {
            return true;
        }

        $correlationId = $response->get_properties()["correlation_id"] ?? null;
        if (!is_null($correlationId) && $context->getProperty("correlation_id") === $correlationId) {
            return true;
        }

        return false;
    }
}
