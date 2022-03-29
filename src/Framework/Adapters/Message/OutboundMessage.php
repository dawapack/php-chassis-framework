<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

class OutboundMessage extends AbstractMessage implements OutboundMessageInterface
{
    /**
     * @inheritdoc
     */
    public function setBody($body): OutboundMessage
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setHeaders(array $headers): OutboundMessage
    {
        $this->headers = isset($this->headers)
            ? array_merge($this->headers, $headers)
            : $headers;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setHeader(string $name, $value): OutboundMessage
    {
        if (!isset($this->headers)) {
            $this->headers = [];
        }
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperties(array $properties): OutboundMessage
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperty(string $name, $value): OutboundMessage
    {
        $this->properties[$name] = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDefaultProperties(): OutboundMessage
    {
        return $this->setProperties(
            $this->messageBus->getDefaultProperties()
        );
    }

    /**
     * @inheritdoc
     */
    public function toMessageBus()
    {
        return $this->messageBus->convert(
            $this->body,
            $this->properties,
            $this->headers
        );
    }
}
