<?php

declare(strict_types=1);

namespace Chassis\Framework\Routers\Routes;

use Chassis\Application;
use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Services\AbstractService;
use DateTime;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Chassis\Helpers\app;

class RouteNotFound
{
    protected const LOGGER_COMPONENT_PREFIX = "route_not_found";
    protected const STATUS_CODE = 404;
    protected const STATUS_MESSAGE = 'NOT FOUND';

    private Application $application;

    /**
     * @param Application|null $application
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(Application $application = null)
    {
        $this->application = is_null($application) ? app() : $application;
    }

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
            $this->application->logger()->warning(
                "Route not found",
                [
                    "component" => self::LOGGER_COMPONENT_PREFIX,
                    "message_properties" => $message->getProperties(),
                ]
            );

            return null;
        }

        // add mandatory headers
        $headers = [
            "version" => AbstractService::DEFAULT_VERSION,
            "dateTime" => (new DateTime())->format(AbstractService::DEFAULT_DATETIME_FORMAT),
            "statusCode" => self::STATUS_CODE,
            "statusMessage" => self::STATUS_MESSAGE
        ];

        return $this->application->get(OutboundMessageInterface::class)
            ->setDefaultProperties()
            ->setHeaders($headers)
            ->setBody(AbstractService::DEFAULT_PAYLOAD);
    }
}
