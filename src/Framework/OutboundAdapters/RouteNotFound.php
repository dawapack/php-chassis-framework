<?php

declare(strict_types=1);

namespace Chassis\Framework\OutboundAdapters;

use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Exceptions\MessageBagFormatException;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function Chassis\Helpers\app;

class RouteNotFound extends OutboundAbstractAdapter
{
    protected const LOGGER_NOT_FOUND_COMPONENT = "route_not_found";

    /**
     * Use empty channel name (AMQP default)
     *
     * @var string
     */
    protected string $channelName = "";

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * @param MessageBagInterface $context
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws MessageBagFormatException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(MessageBagInterface $context): bool
    {
        if (!empty($context->getProperty("reply_to"))) {
            $this->send($this->createResponseMessage($context));
        }

        // log as info
        $this->application->logger()->info(
            "route not found",
            [
                "component" => self::LOGGER_NOT_FOUND_COMPONENT,
                "for_context" => $context->getProperties()
            ]
        );

        return true;
    }

    /**
     * @param MessageBagInterface $context
     *
     * @return BrokerResponse
     * @throws MessageBagFormatException
     * @throws JsonException
     */
    private function createResponseMessage(MessageBagInterface $context): BrokerResponse
    {
        return (new BrokerResponse([]))
            ->fromContext($context)
            ->setStatus(404, "NOT FOUND");
    }
}
