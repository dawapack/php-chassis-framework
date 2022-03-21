<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Application;
use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;
use Chassis\Framework\Bus\AMQP\Message\Exceptions\MessageBodyContentTypeException;
use JsonException;

class AbstractService implements ServiceInterface
{
    protected const LOGGER_COMPONENT_PREFIX = "application_service_";

    protected Application $app;
    private InboundMessageInterface $inboundMessage;
    private OutboundMessageInterface $outboundMessage;

    /**
     * @param InboundMessageInterface $inboundMessage
     * @param OutboundMessageInterface $outboundMessage
     * @param Application $application
     */
    public function __construct(
        InboundMessageInterface $inboundMessage,
        OutboundMessageInterface $outboundMessage,
        Application $application
    ) {
        $this->app = $application;
        $this->inboundMessage = $inboundMessage;
        $this->outboundMessage = $outboundMessage;
    }

    /**
     * @return array
     */
    protected function getMessageProperties(): array
    {
        return $this->inboundMessage->getProperties();
    }

    /**
     * @return array
     */
    protected function getMessageHeaders(): array
    {
        return $this->inboundMessage->getHeaders();
    }

    /**
     * @return mixed
     *
     * @throws MessageBodyContentTypeException
     * @throws JsonException
     */
    protected function getMessageBody()
    {
        return $this->inboundMessage->getBody();
    }
}
