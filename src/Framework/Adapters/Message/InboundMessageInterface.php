<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

use Chassis\Framework\Bus\Exceptions\MessageBusException;
use Chassis\Framework\Bus\MessageBusInterface;
use JsonException;

interface InboundMessageInterface extends MessageInterface
{
    /**
     * @return mixed
     *
     * @throws JsonException
     * @throws MessageBusException
     */
    public function getBody();

    /**
     * @return array
     */
    public function getHeaders(): ?array;

    /**
     * @return array
     */
    public function getProperties(): ?array;

    /**
     * @param MessageBusInterface $messageBus
     *
     * @return void
     */
    public function setMessage(MessageBusInterface $messageBus): void;
}
