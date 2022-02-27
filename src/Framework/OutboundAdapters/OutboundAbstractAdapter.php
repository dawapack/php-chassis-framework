<?php

declare(strict_types=1);

namespace DaWaPack\OutboundAdapters;

use Chassis\Application;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Amqp\Streamers\InfrastructureStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamer;
use Chassis\Framework\Brokers\Amqp\Streamers\PublisherStreamerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use phpDocumentor\Reflection\Types\This;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class OutboundAbstractAdapter implements BrokerOutboundAdapterInterface
{
    protected Application $application;
    protected string $channelName;
    protected string $routingKey;
    protected string $replyTo;
    protected bool $isSyncOverAsync = false;

    /**
     * @var BrokerRequest|BrokerResponse
     */
    private $message;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @inheritdoc
     */
    public function setMessage(MessageBagInterface $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function push(): void
    {
        if ($this->isSyncOverAsync) {
            $this->channelName = "";
            $this->replyTo = $this->createCallbackQueue();
        }
        // Setup message bindings
        $this->setMessageBindings();

        /** @var PublisherStreamer $publisher */
        $publisher = $this->application->get(PublisherStreamerInterface::class);
        $publisher->publish($this->message, $this->channelName);
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
                if (is_null($response)) {
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

    /**
     * @return void
     */
    protected function setMessageBindings(): void
    {
        $this->message
            ->setChannelName($this->channelName)
            ->setRoutingKey($this->routingKey)
            ->setReplyTo($this->replyTo);
    }
}
