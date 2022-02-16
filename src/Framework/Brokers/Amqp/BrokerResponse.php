<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp;

use Chassis\Framework\Brokers\Amqp\MessageBags\AbstractMessageBag;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

class BrokerResponse extends AbstractMessageBag
{
    /**
     * @param MessageBagInterface $messageBag
     *
     * @return $this
     */
    public function fromContext(MessageBagInterface $messageBag): BrokerResponse
    {
        // response is allowed only for BrokerResponse instance type having reply to property set
        if (!($messageBag instanceof BrokerRequest) || is_null($messageBag->getProperty("reply_to"))) {
            return $this;
        }
        // copy properties context to response
        $this->bindings->routingKey = $messageBag->getProperty("reply_to");
        $this->properties->correlation_id = $messageBag->getProperty("correlation_id");
        if (isset($messageBag->properties->application_headers["jobId"])) {
            $this->properties->application_headers["jobId"] = $messageBag->properties->application_headers["jobId"];
        }
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setHeader(string $name, $value): BrokerResponse
    {
        $this->properties->application_headers[$name] = $value;
        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers): BrokerResponse
    {
        $this->properties->application_headers = array_merge(
            $this->properties->application_headers,
            $headers
        );
        return $this;
    }

    public function setStatus(int $code, string $message = ""): BrokerResponse
    {
        $this->setHeaders([
            'statusCode' => $code,
            'statusMessage' => $message,
        ]);
        return $this;
    }
}
