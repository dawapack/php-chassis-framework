<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Inbound;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\AsyncContractInterface;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnectorInterface;
use Chassis\Framework\Routers\InboundRouterInterface;
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
    public const ITERATE_WAIT = 0.5;

    private const LOGGER_COMPONENT_PREFIX = 'amqp_inbound_bus_';

    private AMQPConnectorInterface $connector;
    private AsyncContractInterface $asyncContract;
    private InboundMessageInterface $inboundMessage;
    private InboundRouterInterface $inboundRouter;
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
     * @param InboundMessageInterface $inboundMessage
     * @param InboundRouterInterface $inboundRouter
     * @param LoggerInterface $logger
     */
    public function __construct(
        AMQPConnectorInterface $connector,
        AsyncContractInterface $asyncContract,
        InboundMessageInterface $inboundMessage,
        InboundRouterInterface $inboundRouter,
        LoggerInterface $logger
    ) {
        $this->connector = $connector;
        $this->asyncContract = $asyncContract;
        $this->inboundMessage = $inboundMessage;
        $this->inboundRouter = $inboundRouter;
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
            if (!$this->amqpChannel->is_open()) {
                usleep((int)(self::ITERATE_WAIT*1000000));
            } else {
                $this->amqpChannel->wait(null, false, self::ITERATE_WAIT);
            }
        } catch (AMQPTimeoutException $reason) {
            // fault-tolerant - this is a normal behaviour
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
                    $message = null;
                }
                // wait a while - prevent CPU load
                usleep(5000);
            } while ($until > time());
            // timed out?
            if (!($message instanceof AMQPMessage)) {
                $amqpChannel->close();
                return null;
            }
            // ack the message - mandatory
            $message->ack();
            // need to clone inbound message
            $inboundMessage = clone $this->inboundMessage;
            $inboundMessage->setMessage($message);
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
                // need to clone inbound message
                $inboundMessage = clone $_this->inboundMessage;
                $inboundMessage->setMessage($message);
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
        return $this->asyncContract->transform($channel)->toConsumeArguments($options);
    }
}
