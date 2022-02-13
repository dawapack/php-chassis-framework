<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Streamers;

use Closure;
use Chassis\Framework\Brokers\Amqp\BrokerRequest;
use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Exceptions\StreamerChannelNameNotFoundException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

use function Chassis\Helpers\app;

class SubscriberStreamer extends AbstractStreamer implements SubscriberStreamerInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "subscriber_streamer_";
    private const QOS_PREFETCH_SIZE = 0;
    private const QOS_PREFETCH_COUNT = 1;
    // TODO: investigate this - set to true rise an error RabbitMQ side
    private const QOS_PER_CONSUMER = false;

    private AMQPChannel $streamChannel;
    private string $handler;
    private int $qosPrefetchSize;
    private int $qosPrefetchCount;
    private bool $qosPerConsumer;
    private bool $consumed = false;

    /**
     * @var BrokerRequest|BrokerResponse|null
     */
    private $messageBag;

    /**
     * @inheritdoc
     */
    public function setHandler(string $handler): SubscriberStreamer
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHandler(): ?string
    {
        return $this->handler ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setQosPrefetchSize(int $qosPrefetchSize): SubscriberStreamer
    {
        $this->qosPrefetchSize = $qosPrefetchSize;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQosPrefetchSize(): ?int
    {
        return $this->qosPrefetchSize ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setQosPrefetchCount(int $qosPrefetchCount): SubscriberStreamer
    {
        $this->qosPrefetchCount = $qosPrefetchCount;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQosPrefetchCount(): ?int
    {
        return $this->qosPrefetchCount ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setQosPerConsumer(bool $qosPerConsumer): SubscriberStreamer
    {
        $this->qosPerConsumer = $qosPerConsumer;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isQosPerConsumer(): bool
    {
        return $this->qosPerConsumer;
    }

    public function consumed(): bool
    {
        return $this->consumed;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        $messageBag = $this->messageBag ?? null;
        if ($this->consumed()) {
            unset($this->messageBag);
            $this->consumed = false;
        }
        return $messageBag;
    }

    /**
     * @inheritdoc
     */
    public function consume(?Closure $callback = null): SubscriberStreamer
    {
        $this->streamChannel = $this->getChannel();
        $this->setStreamChannelQOS();
        $this->streamChannel->basic_consume(...$this->toFunctionArguments($callback));

        $this->logger->debug(
            "debug info",
            [
                'component' => self::LOGGER_COMPONENT_PREFIX . "start_consuming",
                'channel' => $this->channelName
            ]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function iterate(): void
    {
        try {
            $this->streamChannel->wait(null, false, 0.25);
        } catch (AMQPTimeoutException $reason) {
            // Rise this exception on timeout - this is a normal behaviour
        }
        // check heartbeat
        $this->checkHeartbeat();
    }

    /**
     * @return void
     */
    protected function setStreamChannelQOS(): void
    {
        !isset($this->qosPrefetchSize) && $this->setDefaultQOSPrefetchSize();
        !isset($this->qosPrefetchCount) && $this->setDefaultQOSPrefetchCount();
        !isset($this->qosPerConsumer) && $this->setDefaultQOSPerConsumer();

        $this->streamChannel->basic_qos(
            $this->qosPrefetchSize,
            $this->qosPrefetchCount,
            $this->qosPerConsumer
        );
    }

    /**
     * @param AMQPMessage $message
     * @param bool $acknowledge
     * @param bool $holdData
     *
     * @return BrokerRequest|BrokerResponse|null
     */
    protected function handleData(AMQPMessage $message, bool $acknowledge, bool $holdData = true)
    {
        try {
            // create message bag from AMQP message
            $messageBag = new $this->handler(
                $message->getBody(),
                $message->get_properties(),
                $message->getConsumerTag()
            );

            if ($holdData === true) {
                $this->messageBag = $messageBag;
            }

            if ($acknowledge === true) {
                $message->ack();
            }

            $this->consumed = true;

            return $messageBag;
        } catch (Throwable $reason) {
            app()->logger()->error(
                $reason->getMessage(),
                [
                    'component' => self::LOGGER_COMPONENT_PREFIX . 'handle_data_exception',
                    'error' => $reason
                ]
            );
        }

        // negative acknowledgement
        $message->nack();

        return null;
    }

    /**
     * @return Closure
     */
    protected function getDefaultConsumerCallback(): Closure
    {
        $subscriber = $this;
        return function (AMQPMessage $message) use ($subscriber) {
            $channel = $subscriber->getContractManager()->getChannel($subscriber->getChannelName());
            // handle message
            $subscriber->handleData($message, $channel->operationBindings->ack);
        };
    }

    /**
     * @return void
     */
    private function setDefaultQOSPrefetchSize(): void
    {
        $this->qosPrefetchSize = self::QOS_PREFETCH_SIZE;
    }

    /**
     * @return void
     */
    private function setDefaultQOSPrefetchCount(): void
    {
        $this->qosPrefetchCount = self::QOS_PREFETCH_COUNT;
    }

    /**
     * @return void
     */
    private function setDefaultQOSPerConsumer(): void
    {
        $this->qosPerConsumer = self::QOS_PER_CONSUMER;
    }

    /**
     * @param Closure|null $callback
     *
     * @return array
     *
     * @throws StreamerChannelNameNotFoundException
     */
    private function toFunctionArguments(?Closure $callback): array
    {
        if (is_null($callback)) {
            $callback = $this->getDefaultConsumerCallback();
        }

        $channelName = $this->getChannelName() ?? "";

        return array_values(
            $this->contractsManager
                ->toBasicConsumeFunctionArguments($channelName, $callback)
        );
    }
}
