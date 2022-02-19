<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

interface RouterInterface
{
    /**
     * @param MessageBagInterface $message
     *
     * @return bool
     */
    public function route(MessageBagInterface $message): bool;
}
