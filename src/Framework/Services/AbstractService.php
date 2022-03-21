<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Application;
use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessageInterface;

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
}
