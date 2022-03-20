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
    }

    /**
     * @inheritdoc
     */
    public function getHeader(string $name)
    {
        return $this->messageBus->getHeader($name);
    }

    /**
     * @inheritdoc
     */
    public function getProperty(string $name)
    {
        return $this->messageBus->getProperty($name);
    }
}
