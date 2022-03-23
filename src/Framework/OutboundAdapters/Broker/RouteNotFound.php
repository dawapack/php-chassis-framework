<?php

declare(strict_types=1);

namespace Chassis\Framework\OutboundAdapters\Broker;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Chassis\Helpers\app;

class RouteNotFound
{
    /**
     * Nobody cares about the implementation
     *
     * @param InboundMessageInterface $message
     *
     * @return OutboundMessageInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(InboundMessageInterface $message): ?OutboundMessageInterface
    {
        if (empty($message->getProperty("reply_to"))) {
            return null;
        }

        return app(OutboundMessageInterface::class)
            ->setDefaultProperties()
            ->setHeaders(["statusCode" => 404, "statusMessage" => "NOT FOUND"])
            ->setBody([]);
    }
}
