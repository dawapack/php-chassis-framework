<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Outbound;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\AsyncApi\AsyncContractInterface;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnectorInterface;
use Chassis\Framework\Bus\AMQP\Message\Exceptions\MessageBodyContentTypeException;
use Chassis\Framework\Bus\AMQP\Outbound\AckNackHandlers\PublishAckHandler;
use Chassis\Framework\Bus\AMQP\Outbound\AckNackHandlers\PublishNackHandler;
use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerInterface;
use Throwable;

class AMQPOutboundBus implements AMQPOutboundBusInterface
{
    private const LOGGER_COMPONENT_PREFIX = 'amqp_outbound_bus_';
    private const PUBLISH_ACK_NACK_TIMEOUT = 5;
    private const RESPONSE_TYPE_SUFFIX = 'Response';

    private AMQPConnectorInterface $connector;
    private AsyncContractInterface $asyncContract;
    private LoggerInterface $logger;

    /**
     * @param AMQPConnectorInterface $connector
     * @param AsyncContractInterface $asyncContract
     * @param LoggerInterface $logger
     */
    public function __construct(
        AMQPConnectorInterface $connector,
        AsyncContractInterface $asyncContract,
        LoggerInterface $logger
    ) {
        $this->connector = $connector;
        $this->asyncContract = $asyncContract;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function publish(
        OutboundMessageInterface $message,
        string $channel,
        string $routingKey,
        int $timeout = self::PUBLISH_ACK_NACK_TIMEOUT
    ): void {
        try {
            $amqpChannel = $this->connector->getChannel();
            $this->enablePublishConfirmMode($amqpChannel);
            $amqpChannel->basic_publish(
                ...$this->toBasicPublishArguments($message, $channel, $routingKey)
            );
            $amqpChannel->wait_for_pending_acks($timeout);
        } catch (Throwable $reason) {
            $this->logger->error(
                $reason->getMessage(),
                [
                    'component' => self::LOGGER_COMPONENT_PREFIX . "publish",
                    'error' => $reason,
                ]
            );
            throw $reason;
        }

        // we don't need this amqp channel anymore
        if ($amqpChannel->is_open()) {
            $amqpChannel->close();
        }
    }

    public function publishResponse(OutboundMessageInterface $message, InboundMessageInterface $context)
    {
        // a response must have a routing key
        $routingKey = $context->getProperty("reply_to");
        if (empty($routingKey)) {
            return null;
        }

        // set mandatory properties & headers
        $message->setProperty("type", $context->getProperty("type") . self::RESPONSE_TYPE_SUFFIX);
        $message->setProperty("correlation_id", $context->getProperty("correlation_id"));
        $message->setHeader("jobId", $context->getheader("jobId"));
        $message->setProperty("reply_to", null);

        // publish the response
        $this->publish($message, "amqp/default", $routingKey);

        return null;
    }

    /**
     * @param AMQPChannel $amqpChannel
     *
     * @return void
     */
    protected function enablePublishConfirmMode(AMQPChannel $amqpChannel): void
    {
        $amqpChannel->set_ack_handler(new PublishAckHandler());
        $amqpChannel->set_nack_handler(new PublishNackHandler());
        $amqpChannel->confirm_select();
    }

    /**
     * @param OutboundMessageInterface $message
     * @param string $channel
     * @param string $routingKey
     *
     * @return array
     *
     * @throws MessageBodyContentTypeException
     */
    protected function toBasicPublishArguments(
        OutboundMessageInterface $message,
        string $channel,
        string $routingKey
    ): array {
        return $this->asyncContract
            ->transform($channel)
            ->toPublishArguments(
                $message->toMessageBus(),
                $routingKey
            );
    }
}
