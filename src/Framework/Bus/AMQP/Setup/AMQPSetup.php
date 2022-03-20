<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Setup;

use Chassis\Framework\AsyncApi\AsyncContractInterface;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnectorInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Psr\Log\LoggerInterface;
use Throwable;

class AMQPSetup implements AMQPSetupInterface
{
    private const LOGGER_COMPONENT_PREFIX = "amqp_setup_";
    private const EXCHANGE_TYPE = 'exchange';
    private const QUEUE_TYPE = 'queue';

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
    public function setup(bool $passive = true): void
    {
        try {
            // declare queues & exchanges
            $this->declareChannels($passive);
            // declare bindings
            $this->declareBindings();
        } catch (Throwable $reason) {
            $this->logger->error(
                $reason->getMessage(),
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "setup_exception",
                    "error" => $reason
                ]
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function purge(string $channel): bool
    {
        $channels = $this->asyncContract->getChannels();
        if (
            !isset($channels[$channel])
            || $this->asyncContract->getChannelType($channel) !== self::QUEUE_TYPE
        ) {
            return false;
        }

        $amqpChannel = $this->connector->getChannel();
        $purged = false;
        try {
            $amqpChannel->queue_purge(
                $this->asyncContract->getChannelBindings($channel)->name
            );
            $purged = true;
        } catch (Throwable $reason) {
            $this->logger->error(
                $reason->getMessage(),
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX . "purge_exception",
                    "error" => $reason
                ]
            );
        }
        $this->closeAmqpChannel($amqpChannel);

        return $purged;
    }

    /**
     * @param bool $passive
     *
     * @return void
     */
    protected function declareChannels(bool $passive): void
    {
        $channels = $this->asyncContract->getChannels();
        // loop through channels
        foreach ($channels as $channel => $data) {
            // never declare the default exchange
            if ($channel === "amqp/default") {
                continue;
            }
            if ($this->asyncContract->getChannelType($channel) === self::EXCHANGE_TYPE) {
                $this->declareExchange($channel, $passive);
                continue;
            }
            $this->declareQueue($channel, $passive);
        }
    }

    /**
     * @param string $channel
     * @param bool $passive
     *
     * @return void
     */
    protected function declareExchange(string $channel, bool $passive): void
    {
        $amqpChannel = $this->connector->getChannel();
        try {
            // will throw an exception if the exchange doesn't exist and passive = true
            $amqpChannel->exchange_declare(
                ...$this->asyncContract->transform($channel)->toExchangeDeclareArguments(["passive" => $passive])
            );
        } catch (AMQPProtocolChannelException $reason) {
            // close previous channel - mandatory
            $this->closeAmqpChannel($amqpChannel);

            // force exchange declaration - must use a clean channel
            $amqpChannel = $this->connector->getChannel();
            $amqpChannel->exchange_declare(
                ...$this->asyncContract->transform($channel)->toExchangeDeclareArguments(["passive" => false])
            );
        }
        $this->closeAmqpChannel($amqpChannel);
    }

    /**
     * @param string $channel
     * @param bool $passive
     *
     * @return void
     */
    protected function declareQueue(string $channel, bool $passive): void
    {
        $amqpChannel = $this->connector->getChannel();
        try {
            // will throw an exception if the queue doesn't exist and passive = true
            $amqpChannel->queue_declare(
                ...$this->asyncContract->transform($channel)->toQueueDeclareArguments(["passive" => $passive])
            );
        } catch (AMQPProtocolChannelException $reason) {
            // close previous channel - mandatory
            $this->closeAmqpChannel($amqpChannel);

            // force queue declaration
            $amqpChannel = $this->connector->getChannel();
            $amqpChannel->queue_declare(
                ...$this->asyncContract->transform($channel)->toQueueDeclareArguments(["passive" => false])
            );
        }
        $this->closeAmqpChannel($amqpChannel);
    }

    /**
     * @return void
     */
    protected function declareBindings(): void
    {
        $channels = $this->asyncContract->getChannels();
        // loop through channels
        foreach ($channels as $channel => $data) {
            // never bind something to default exchange
            if ($channel === "amqp/default") {
                continue;
            }
            $operation = $this->asyncContract->getOperationBindings($channel);
            if (!isset($operation->cc)) {
                continue;
            }
            $channelBindingType = $this->asyncContract->getChannelType($channel);
            $channelBindingName = $this->asyncContract->getChannelBindings($channel)->name;
            // create routes
            foreach ($operation->cc as $rule) {
                $this->createRoute($channelBindingType, $channelBindingName, $rule);
            }
        }
    }

    /**
     * @param string $channelBindingType
     * @param string $channelBindingName
     * @param string $rule
     *
     * @return void
     */
    protected function createRoute(
        string $channelBindingType,
        string $channelBindingName,
        string $rule
    ): void {
        $ruleElements = explode("|", $rule);
        if (count($ruleElements) != 2) {
            return;
        }
        // crate route
        $amqpChannel = $this->connector->getChannel();
        if ($channelBindingType === self::QUEUE_TYPE) {
            $amqpChannel->queue_bind($channelBindingName, $ruleElements[0], $ruleElements[1]);
        } else {
            $amqpChannel->exchange_bind($ruleElements[0], $channelBindingName, $ruleElements[1]);
        }
        $this->closeAmqpChannel($amqpChannel);
    }

    /**
     * @param AMQPChannel|mixed $amqpChannel
     *
     * @return void
     */
    protected function closeAmqpChannel($amqpChannel)
    {
        if (($amqpChannel instanceof AMQPChannel) && $amqpChannel->is_open()) {
            $amqpChannel->close();
        }
    }
}
