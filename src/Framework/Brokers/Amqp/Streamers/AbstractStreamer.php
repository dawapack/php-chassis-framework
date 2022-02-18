<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Streamers;

use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerChannel;
use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\ChannelBindings;
use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\OperationBindings;
use Chassis\Framework\Brokers\Amqp\Contracts\ContractsManager;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class AbstractStreamer implements StreamerInterface
{
    public const CONSUME_OPERATION = 'consume';
    public const PUBLISH_OPERATION = 'publish';
    protected const LOGGER_COMPONENT_PREFIX = "abstract_streamer_";

    protected AMQPStreamConnection $streamerConnection;
    protected ContractsManager $contractsManager;
    protected LoggerInterface $logger;
    protected array $exchangeDeclareMapper = [
        'name' => null,
        'type' => null,
        'passive' => true,
        'durable' => false,
        'autoDelete' => true,
        'internal' => false,
        'nowait' => false,
        'arguments' => [],
        'ticket' => null
    ];
    protected array $queueDeclareMapper = [
        'name' => null,
        'passive' => true,
        'durable' => false,
        'exclusive' => false,
        'autoDelete' => true,
        'nowait' => false,
        'arguments' => [
            'x-max-priority' => 5,
        ],
        'ticket' => null
    ];
    protected string $channelName;
    protected string $exchangeName;
    protected string $queueName;
    protected array $availableChannels = [
        'exchanges' => [],
        'queues' => [],
    ];
    protected int $heartbeatLastActivity;

    /**
     * AbstractStreamer constructor.
     *
     * @param AMQPStreamConnection $streamerConnection
     * @param ContractsManager $contractsManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        AMQPStreamConnection $streamerConnection,
        ContractsManager $contractsManager,
        LoggerInterface $logger
    ) {
        $this->heartbeatLastActivity = time();
        $this->streamerConnection = $streamerConnection;
        $this->contractsManager = $contractsManager;
        $this->logger = $logger;
        $this->transformDeclareMapperArguments();
    }

//    /**
//     * AbstractStreamer destructor.
//     */
//    public function __destruct()
//    {
//        $this->disconnect();
//    }

    /**
     * @inheritdoc
     */
    public function getQueueName(): ?string
    {
        return $this->queueName ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getChannelName(): ?string
    {
        return $this->channelName ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setChannelName(string $channelName): self
    {
        $this->channelName = $channelName;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getChannel(?int $id = null): AMQPChannel
    {
        // create new channel using the given stream connection
        return $this->streamerConnection->channel($id);
    }

    public function getContractManager(): ContractsManager
    {
        return $this->contractsManager;
    }

//    /**
//     * @inheritDoc
//     */
//    public function disconnect(): bool
//    {
//        try {
//            if (!$this->streamerConnection->isConnected()) {
//                throw new AMQPConnectionClosedException();
//            }
//            if (isset($this->streamerConnection)) {
//                $this->streamerConnection->close();
//            }
//        } catch (Throwable $reason) {
//            // Fault-tolerant
//            return false;
//        }
//        return true;
//    }

    /**
     * @inheritdoc
     */
    protected function checkHeartbeat(): void
    {
        $heartbeat = $this->streamerConnection->getHeartbeat();
        if (
            $heartbeat === 0
            || !$this->streamerConnection->isConnected()
            || $this->streamerConnection->isWriting()
        ) {
            return;
        }

        $interval = ceil($heartbeat / 2);
        if (time() > ($this->heartbeatLastActivity + $interval)) {
            $this->streamerConnection->checkHeartBeat();
            $this->heartbeatLastActivity = time();
        }
    }

    protected function channelDeclare(BrokerChannel $brokerChannel, bool $declareBindings): void
    {
        // exchange declaration
        if ($brokerChannel->channelBindings->is === "routingKey") {
            $this->exchangeDeclare($brokerChannel->channelBindings);
            return;
        }
        // queue declaration
        $this->queueDeclare($brokerChannel->channelBindings);
        if (count($brokerChannel->operationBindings->cc) > 0 && $declareBindings) {
            $this->channelBind($brokerChannel->operationBindings, $brokerChannel->channelBindings->name);
        }
    }

    protected function channelDelete(BrokerChannel $brokerChannel): void
    {
        // exchange deletion
        if ($brokerChannel->channelBindings->is === "routingKey") {
            $this->exchangeDelete($brokerChannel->channelBindings);
            return;
        }
        // queue & bindings deletion
        $this->queueDelete($brokerChannel->channelBindings);
    }

    protected function exchangeDeclare(ChannelBindings $channelBindings): void
    {
        $channel = $this->getChannel();
        try {
            $functionArguments = array_merge(
                $this->exchangeDeclareMapper,
                $channelBindings->toFunctionArguments(false)
            );
            // will throw an exception if the exchange doesn't exist - passive = true
            $channel->exchange_declare(...array_values($functionArguments));
        } catch (AMQPProtocolChannelException $reason) {
            // force exchange declaration
            $functionArguments = array_merge(
                $this->exchangeDeclareMapper,
                $channelBindings->toFunctionArguments(false),
                ['passive' => false]
            );
            $channel = $this->getChannel();
            $channel->exchange_declare(...array_values($functionArguments));
        }
        $this->availableChannels["exchanges"][$functionArguments["name"]] = $functionArguments;
        $channel->close();
    }

    protected function exchangeDelete(ChannelBindings $channelBindings)
    {
        $functionArguments = array_intersect_key(
            $channelBindings->toFunctionArguments(false),
            ['name' => null]
        );
        $channel = $this->getChannel();
        $channel->exchange_delete(...array_values($functionArguments));
        unset($this->availableChannels["exchanges"][$functionArguments["name"]]);
        $channel->close();
    }

    protected function queueDeclare(ChannelBindings $channelBindings): void
    {
        $channel = $this->getChannel();
        try {
            $functionArguments = array_merge(
                $this->queueDeclareMapper,
                $channelBindings->toFunctionArguments(false)
            );
            // will throw an exception if the queue doesn't exist - passive = true
            $channel->queue_declare(...array_values($functionArguments));
        } catch (AMQPProtocolChannelException $reason) {
            // force exchange declaration
            $functionArguments = array_merge(
                $this->queueDeclareMapper,
                $channelBindings->toFunctionArguments(false),
                ['passive' => false]
            );
            $channel = $this->getChannel();
            $channel->queue_declare(...array_values($functionArguments));
        }
        $this->availableChannels["queues"][$functionArguments["name"]] = $functionArguments;
        $channel->close();
    }

    protected function queueDelete(ChannelBindings $channelBindings)
    {
        $functionArguments = array_intersect_key(
            $channelBindings->toFunctionArguments(false),
            ['name' => null]
        );
        $channel = $this->getChannel();
        $channel->queue_delete(...array_values($functionArguments));
        unset($this->availableChannels["queues"][$functionArguments["name"]]);
        $channel->close();
    }

    protected function channelBind(OperationBindings $operationBindings, string $queue): void
    {
        foreach ($operationBindings->cc as $routingKey) {
            $functionArguments = array_merge(
                [$queue],
                explode("|", $routingKey)
            );
            if (count($functionArguments) != 3) {
                continue;
            }
            $channel = $this->getChannel();
            $channel->queue_bind(...$functionArguments);
            $channel->close();
        }
    }

    private function transformDeclareMapperArguments(): void
    {
        // arguments transformation - AMQPTable format is required
        $this->queueDeclareMapper["arguments"] = new AMQPTable($this->queueDeclareMapper["arguments"]);
        $this->exchangeDeclareMapper["arguments"] = new AMQPTable($this->exchangeDeclareMapper["arguments"]);
    }
}
