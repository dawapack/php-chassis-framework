<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp;

use Chassis\Framework\Brokers\Amqp\MessageBags\AbstractMessageBag;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;
use Chassis\Framework\Brokers\Amqp\MessageBags\ResponseMessageBagInterface;

class BrokerResponse extends AbstractMessageBag implements ResponseMessageBagInterface
{
    /**
     * @inheritdoc
     */
    public function fromContext(MessageBagInterface $context): BrokerResponse
    {
        // response is allowed only for BrokerResponse instance type having reply to property set
        if (!($context instanceof BrokerRequest) || is_null($context->getProperty("reply_to"))) {
            return $this;
        }
        // copy & adapt context properties to response
        $this->bindings->routingKey = $context->getProperty("reply_to");
        $this->properties->correlation_id = $context->getProperty("correlation_id");
        $this->properties->type = $context->getProperty("type") . "Response";
        if (isset($context->properties->application_headers["jobId"])) {
            $this->properties->application_headers["jobId"] = $context->properties->application_headers["jobId"];
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $code, string $message = ""): BrokerResponse
    {
        $this->setHeaders([
            'statusCode' => $code,
            'statusMessage' => $message,
        ]);
        return $this;
    }
}
