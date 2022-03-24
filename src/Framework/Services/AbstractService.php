<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Framework\Adapters\Message\ApplicationMessageInterface;
use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Routers\OutboundRouterInterface;
use DateTime;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Chassis\Helpers\app;

abstract class AbstractService implements ServiceInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "application_service_";
    protected const DEFAULT_VERSION = '1.0.0';
    protected const DEFAULT_DATETIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    protected InboundMessageInterface $message;

    /**
     * @param InboundMessageInterface $message
     */
    public function __construct(InboundMessageInterface $message)
    {
        $this->message = $message;
    }

    /**
     * @param ApplicationMessageInterface $applicationMessage
     *
     * @return OutboundMessage
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function response(ApplicationMessageInterface $applicationMessage): OutboundMessage
    {
        return $this->createOutboundMessage($applicationMessage);
    }

    /**
     * @param string $operation
     * @param ApplicationMessageInterface $applicationMessage
     *
     * @return InboundMessageInterface|null
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function send(string $operation, ApplicationMessageInterface $applicationMessage): ?InboundMessageInterface
    {
        return app(OutboundRouterInterface::class)
            ->route($operation, $this->createOutboundMessage($applicationMessage));
    }

    /**
     * @param ApplicationMessageInterface $applicationMessage
     *
     * @return OutboundMessage
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createOutboundMessage(ApplicationMessageInterface $applicationMessage): OutboundMessage
    {
        // add mandatory headers
        $headers = array_merge(
            $applicationMessage->getHeaders(),
            [
                "version" => self::DEFAULT_VERSION,
                "dateTime" => (new DateTime())->format(self::DEFAULT_DATETIME_FORMAT)
            ]
        );
        // add job id
        if (!empty($this->message->getHeader("jobId"))) {
            $headers = array_merge($headers, ["jobId" => $this->message->getHeader("jobId")]);
        }

        return app(OutboundMessageInterface::class)
            ->setDefaultProperties()
            ->setHeaders($headers)
            ->setBody($applicationMessage->getPayload());
    }
}
