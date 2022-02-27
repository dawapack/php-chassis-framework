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
    /**
     * Use empty channel name (AMQP default)
     *
     * @var string
     */
    protected string $channelName = "";

    /**
     * This will be overwritten from context
     *
     * @var string
     */
    protected string $routingKey = "";

    /**
     * Route not found has no reply to property
     *
     * @var string
     */
    protected string $replyTo = "";

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
     * @throws JsonException
     * @throws MessageBagFormatException
     */
    public function __invoke(MessageBagInterface $context): bool
    {
        if (!empty($context->getProperty("reply_to"))) {
            $this->routingKey = $context->getProperty("reply_to");
            $this->setMessage($this->createResponseMessage($context))
                ->push();
        }

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
