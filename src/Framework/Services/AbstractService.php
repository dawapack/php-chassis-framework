<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Routers\OutboundRouterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Chassis\Helpers\app;

class AbstractService implements ServiceInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "application_service_";
    protected InboundMessageInterface $message;

    /**
     * @param InboundMessageInterface $message
     */
    public function __construct(InboundMessageInterface $message)
    {
        $this->message = $message;
    }

    /**
     * @param array|object|string $body
     * @param array $headers
     *
     * @return OutboundMessage
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function response($body, array $headers = []): OutboundMessage
    {
        return $this->createOutboundMessage($body, $headers);
    }

    /**
     * @param string $operation
     * @param array|object|string $body
     * @param array $headers
     *
     * @return InboundMessageInterface|null
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function send(
        string $operation,
        $body,
        array $headers = []
    ): ?InboundMessageInterface {
        return app(OutboundRouterInterface::class)
            ->route($operation, $this->createOutboundMessage($body, $headers));
    }

    /**
     * @param array|object|string $body
     * @param array $headers
     *
     * @return OutboundMessage
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createOutboundMessage($body, array $headers): OutboundMessage
    {
        if (!empty($this->message->getHeader("jobId"))) {
            $headers = array_merge($headers, ["jobId" => $this->message->getHeader("jobId")]);
        }

        return app(OutboundMessageInterface::class)
            ->setDefaultProperties()
            ->setHeaders($headers)
            ->setBody($body);
    }
}
