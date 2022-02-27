<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers;

use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

interface InboundRouterInterface
{
    /**
     * @param MessageBagInterface $message
     *
     * @return MessageBagInterface|bool
     */
    public function route(MessageBagInterface $message);
}
