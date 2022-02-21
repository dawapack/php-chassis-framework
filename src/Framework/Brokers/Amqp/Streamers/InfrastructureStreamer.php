<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Streamers;

class InfrastructureStreamer extends AbstractStreamer
{
    /**
     * @param bool $declareBindings
     *
     * @return int
     */
    public function brokerChannelsSetup(bool $declareBindings = true): int
    {
        $channels = $this->contractsManager->getChannels();
        foreach ($channels as $channel) {
            $this->channelDeclare($channel, $declareBindings);
        }
        return $channels->count();
    }

    public function brokerActiveRpcSetup(): string
    {
        if ($this->application->has("activeRpcResponsesQueue")) {
            return $this->application->get("activeRpcResponsesQueue");
        }
        // create the queue
        return $this->rpcCallbackQueueDeclare();
    }
    /**
     * @return int
     */
    public function brokerChannelsClear(): int
    {
        $channels = $this->contractsManager->getChannels();
        foreach ($channels as $channel) {
            $this->channelDelete($channel);
        }
        return $channels->count();
    }

    /**
     * @param string|null $filter - 'exchanges' or 'queues'
     *
     * @return array
     */
    public function getAvailableChannels(?string $filter = null): array
    {
        return isset($filter) && isset($this->availableChannels[$filter])
            ? $this->availableChannels[$filter]
            : $this->availableChannels;
    }
}
