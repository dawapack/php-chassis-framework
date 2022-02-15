<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger;

use Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject\BrokerChannel;
use Chassis\Framework\Logger\DataTransferObject\ContextBroker;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class LoggerApplicationContext implements LoggerApplicationContextInterface
{
    private ContextBroker $contextBroker;

    /**
     * @inheritDoc
     */
    public function getBrokerContext(): ?ContextBroker
    {
        return $this->contextBroker ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setBrokerContext(string $channelName, BrokerChannel $channel, AMQPMessage $message): void
    {
        $this->contextBroker = new ContextBroker([
            'channelName' => $channelName,
            'bindings' => $channel->toArray(),
            'message' => $this->extractContextMessageElements($message)
        ]);
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        unset($this->contextBroker);
    }

    /**
     * @param AMQPMessage $message
     *
     * @return array
     */
    private function extractContextMessageElements(AMQPMessage $message): array
    {
        $context = [
            'properties' => $message->get_properties(),
            'headers' => [],
            'body' => null,
        ];
        if (
            !empty($context["properties"]["application_headers"])
            && ($context["properties"]["application_headers"] instanceof AMQPTable)
        ) {
            $context["headers"] = $context["properties"]["application_headers"]->getNativeData();
            unset($context["properties"]["application_headers"]);
        }

        return $context;
    }
}
