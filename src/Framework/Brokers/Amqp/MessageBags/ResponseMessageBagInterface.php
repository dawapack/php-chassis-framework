<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\MessageBags;

interface ResponseMessageBagInterface
{
    /**
     * @param MessageBagInterface $context
     *
     * @return $this
     */
    public function fromContext(MessageBagInterface $context): self;

    /**
     * @param int $code
     * @param string $message
     *
     * @return $this
     */
    public function setStatus(int $code, string $message = ""): self;
}
