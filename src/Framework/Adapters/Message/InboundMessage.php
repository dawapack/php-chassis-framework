<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

class InboundMessage extends AbstractMessage implements InboundMessageInterface
{
    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->messageBus->getBody();
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): ?array
    {
        return $this->messageBus->getHeaders();
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): array
    {
        return $this->messageBus->getProperties();
    }

    /**
     * @inheritdoc
     */
    public function setMessage($messageFromBus): void
    {
        $this->messageBus->setMessage($messageFromBus);
    }
}
