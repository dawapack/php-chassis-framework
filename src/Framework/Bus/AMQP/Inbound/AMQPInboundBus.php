<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Inbound;

use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\AsyncContractInterface;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnectorInterface;
use Chassis\Framework\Bus\MessageBusInterface;
use Chassis\Framework\Routers\InboundRouterInterface;
use Chassis\Framework\Threads\DataTransferObject\IPCMessage;
use Chassis\Framework\Threads\InterProcessCommunication\IPCChannelsInterface;
use Chassis\Framework\Threads\InterProcessCommunication\ParallelChannels;
use Closure;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Implements the communication with bus service (AMQP 0.9.1)
 */
class AMQPInboundBus implements AMQPInboundBusInterface
{
    public const ITERATE_NO_CHANNEL_WAIT = 250; // 250ms
    public const ITERATE_WAIT = 0.5; // 500ms

    private const LOGGER_COMPONENT_PREFIX = 'amqp_inbound_bus_';

    private AMQPConnectorInterface $connector;
    private AsyncContractInterface $asyncContract;
    private InboundRouterInterface $inboundRouter;
    private MessageBusInterface $messageBus;
    private IPCChannelsInterface $ipcChannels;
    private LoggerInterface $logger;
    private AMQPChannel $amqpChannel;
    private string $channel;

    /** *****************************************************************************************
     *  global  *           prefetchCount AMQP          *       prefetchCount RabbitMQ          *
     *  *****************************************************************************************
     *  false   * all consumers on the channel          * to each new consumer on the channel   *
     *   true   * all consumers on the connection       * all consumers on the channel          *
     *  *****************************************************************************************
     */
    private array $qos = [
        'prefetchSize' => 0,
        'prefetchCount' => 1,
        'global' => false,
    ];

    /**
     * @param AMQPConnectorInterface $connector
     * @param AsyncContract $asyncContract
     * @param InboundRouterInterface $inboundRouter
     * @param MessageBusInterface $messageBus
     * @param IPCChannelsInterface $ipcChannels
     * @param LoggerInterface $logger
     */
    public function __construct(
        AMQPConnectorInterface $connector,
        AsyncContractInterface $asyncContract,
        InboundRouterInterface $inboundRouter,
        MessageBusInterface $messageBus,
        IPCChannelsInterface $ipcChannels,
        LoggerInterface $logger
    ) {
        $this->connector = $connector;
        $this->asyncContract = $asyncContract;
        $this->inboundRouter = $inboundRouter;
        $this->messageBus = $messageBus;
        $this->ipcChannels = $ipcChannels;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function consume(
        string $channel,
        array $options = [],
        array $qos = []
    ): void {
        $this->channel = $channel;
        // retrieve a new amqp channel
        $this->amqpChannel = $this->connector->getChannel();
        // set quality of service
        $this->setQos($qos);
        // subscribe
        $this->amqpChannel->basic_consume(...$this->toBasicConsumeArguments($channel, $options));
    }

    /**
     * @inheritdoc
     */
    public function iterate(): void
    {
        try {
            /**
             * threads like infrastructure doesn't consume
             * which means the amqp channel will not be set
             */
            if (!isset($this->amqpChannel)) {
                // wait a while - prevent CPU load
                usleep((int)(self::ITERATE_NO_CHANNEL_WAIT * 1000));
            } else {
                $this->amqpChannel->wait(null, false, self::ITERATE_WAIT);
            }
        } catch (AMQPTimeoutException $reason) {
            // timeout fault-tolerant - this is a normal behaviour
        }

        // heartbeat check
        $this->connector->checkHeartbeat();
    }

    /**
     * @inheritdoc
     */
    public function get(string $queueName, ?string $correlationId = null, int $timeout = 30)
    {
        $inboundMessage = null;
        try {
            // retrieve a new channel
            $amqpChannel = $this->connector->getChannel();
            $until = time() + $timeout;
            do {
                /**
                 * wait here until a message will be served
                 * this can exit with AMQPTimoutException - see channel_rpc_timeout
                 */
                $message = $amqpChannel->basic_get($queueName);
                if ($message instanceof AMQPMessage) {
                    // need to compare expected correlation id
                    $messageProperties = $message->get_properties();
                    if (is_null($correlationId) || $messageProperties["correlation_id"] === $correlationId) {
                        break;
                    }
                    // remove this message from the queue and do...while again
                    $message->nack();
                    unset($message);
                }
                // wait a while - prevent CPU load
                usleep(5000);
            } while ($until > time());
            // timed out?
            if (!isset($message) || !($message instanceof AMQPMessage)) {
                $amqpChannel->close();
                return null;
            }
            // ack the message - mandatory
            $message->ack();
            // create inbound message instance
            $inboundMessage = new InboundMessage(
                (clone $this->messageBus)->setMessage($message)
            );
        } catch (Throwable $reason) {
            $this->logger->error(
                $reason->getMessage(),
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "get_response_exception",
                    "error" => $reason
                ]
            );
        }

        // we don't need this amqp channel anymore
        if (isset($amqpChannel) && $amqpChannel->is_open()) {
            $amqpChannel->close();
        }

        return $inboundMessage;
    }

    /**
     * @param array $qos
     *
     * @return void
     */
    protected function setQos(array $qos): void
    {
        // filter & apply given qos values
        $qos = array_merge(
            $this->qos,
            array_intersect_key($qos, $this->qos)
        );
        $this->amqpChannel->basic_qos(...array_values($qos));
    }

    /**
     * @return Closure
     */
    protected function messageHandler(): Closure
    {
        $_this = $this;
        return function (AMQPMessage $message) use ($_this) {
            $ackMessage = ($_this->asyncContract->getOperationBindings($_this->channel))->ack ?? true;
            try {
                // create inbound message instance
                $inboundMessage = new InboundMessage(
                    (clone $this->messageBus)->setMessage($message)
                );
                // route the message
                $_this->inboundRouter->route(
                    $inboundMessage->getProperty("type"),
                    $inboundMessage
                );
                if ($ackMessage) {
                    $message->ack();
                }
            } catch (Throwable $reason) {
                $_this->logger->error(
                    $reason->getMessage(),
                    [
                        'component' => self::LOGGER_COMPONENT_PREFIX . "message_handler_exception",
                        'error' => $reason,
                        'message' => $inboundMessage->getHeaders()
                    ]
                );
            }

            // nack handler - on error
            $message->nack(!$message->isRedelivered());

            // notify worker

            var_dump([__METHOD__, "notify worker"]);

            $_this->ipcChannels->sendTo(
                $_this->ipcChannels->getWorkerChannel(),
                (new IPCMessage())->set(ParallelChannels::METHOD_JOB_PROCESSED)
            );
        };
    }

    /**
     * @param string $channel
     * @param array $options
     *
     * @return array
     */
    protected function toBasicConsumeArguments(string $channel, array $options): array
    {
        // add callback (message handler) to options
        $options = array_merge($options, ['callback' => $this->messageHandler()]);
        // build & return consume function arguments
        return $this->asyncContract->transform($channel)->toConsumeArguments($options);
    }
}
