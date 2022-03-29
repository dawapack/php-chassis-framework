<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

use Chassis\Framework\Bus\MessageBusInterface;

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
    public function setMessage(MessageBusInterface $messageBus): void
    {
        $this->properties = $messageBus->getProperties();
        $this->headers = $messageBus->getHeaders();
        $this->body = $messageBus->getBody();
        // cleanup properties
        unset($this->properties["application_headers"]);
    }
}
