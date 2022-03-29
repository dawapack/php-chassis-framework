<?php

declare(strict_types=1);

namespace Chassis\Framework\Adapters\Message;

interface MessageInterface
{
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getHeader(string $name);

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getProperty(string $name);
}
