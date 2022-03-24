<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

interface ApplicationMessageInterface
{
    /**
     * @param string $name
     * @param $value
     *
     * @return $this
     */
    public function setHeader(string $name, $value): self;

    /**
     * @param int $code
     * @param string $message
     *
     * @return OutboundMessageInterface
     */
    public function toResponse(int $code, string $message = ""): OutboundMessageInterface;

    /**
     * @return OutboundMessageInterface
     */
    public function toRequest(): OutboundMessageInterface;
}
