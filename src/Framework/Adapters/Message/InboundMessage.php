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
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @inheritdoc
     */
    public function setMessage($messageFromBus): void
    {
        $this->properties = $messageFromBus->getProperties();
        $this->headers = $messageFromBus->getHeaders();
        $this->body = $messageFromBus->getBody();
        // cleanup properties
        unset($this->properties["application_headers"]);

    }
}
