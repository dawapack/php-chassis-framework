<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers\Routes;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use DateTime;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function Chassis\Helpers\app;

class RouteNotFound
{
    protected const DEFAULT_VERSION = '1.0.0';
    protected const DEFAULT_DATETIME_FORMAT = 'Y-m-d\TH:i:s.vP';

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

        // add mandatory headers
        $headers = [
            "version" => self::DEFAULT_VERSION,
            "dateTime" => (new DateTime())->format(self::DEFAULT_DATETIME_FORMAT),
            "statusCode" => 404,
            "statusMessage" => "NOT FOUND"
        ];

        return app(OutboundMessageInterface::class)
            ->setDefaultProperties()
            ->setHeaders($headers)
            ->setBody([]);
    }
}
