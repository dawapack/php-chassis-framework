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
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return array
     */
    public function getPayload(): array;
}
