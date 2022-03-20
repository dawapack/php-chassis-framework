<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

use Chassis\Framework\Bus\AMQP\Message\Exceptions\MessageBodyContentTypeException;
use JsonException;

interface InboundMessageInterface extends MessageInterface
{
    /**
     * @return mixed
     *
     * @throws JsonException
     * @throws MessageBodyContentTypeException
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
     * @param mixed $messageFromBus
     *
     * @return void
     */
    public function setMessage($messageFromBus): void;
}
