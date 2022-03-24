<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

use Chassis\Framework\Bus\MessageBusInterface;

class AbstractMessage implements MessageInterface
{
    protected MessageBusInterface $messageBus;

    /**
     * @var string|array|object
     */
    protected $body;
    protected array $headers;
    protected array $properties;

    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
        // extract properties, headers and body
        $this->properties = $messageBus->getProperties();
        $this->headers = $messageBus->getHeaders();
        $this->body = $messageBus->getBody();
        // cleanup properties
        unset($this->properties["application_headers"]);
    }

    /**
     * @inheritdoc
     */
    public function getHeader(string $name)
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getProperty(string $name)
    {
        return $this->properties[$name] ?? null;
    }
}
