<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\MessageBags;

use DateTime;
use Chassis\Framework\Brokers\Amqp\MessageBags\DataTransferObject\BagBindings;
use Chassis\Framework\Brokers\Amqp\MessageBags\DataTransferObject\BagProperties;
use Chassis\Framework\Brokers\Exceptions\MessageBagFormatException;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;
use Throwable;

abstract class AbstractMessageBag implements MessageBagInterface
{
    public const DEFAULT_PRIORITY = 0;
    public const DEFAULT_DELIVERY_MODE = 2;
    public const DEFAULT_TYPE = 'default';
    public const DEFAULT_CONTENT_TYPE = 'application/json';
    public const DEFAULT_CONTENT_ENCODING = 'UTF-8';
    public const DEFAULT_VERSION = '1.0.0';
    public const DEFAULT_DATETIME_FORMAT = 'Y-m-d H:i:s.v';
    public const TEXT_CONTENT_TYPE = 'text/plain';
    public const GZIP_CONTENT_TYPE = 'application/gzip';
    public const JSON_CONTENT_TYPE = self::DEFAULT_CONTENT_TYPE;
    public const MSGPACK_CONTENT_TYPE = 'application/msgpack';

    private const BODY_FORMAT_MISMATCH_MESSAGE = 'body format mismatch';
    private const UNKNOWN_CONTENT_FORMAT_TYPE_MESSAGE = 'unknown content format type';

    protected BagProperties $properties;
    protected BagBindings $bindings;

    /**
     * @var mixed
     */
    protected $body;

    /**
     * @param mixed $body
     * @param array $properties
     * @param string|null $consumerTag
     *
     * @throws JsonException
     * @throws MessageBagFormatException
     */
    public function __construct(
        $body,
        array $properties = [],
        ?string $consumerTag = null
    ) {
        $this->bindings = new BagBindings(["consumerTag" => $consumerTag]);
        if (is_null($consumerTag)) {
            $this->properties = $this->fulfillProperties($properties);
            $this->body = $body;
        } else {
            $this->properties = $this->decodeApplicationHeaders($properties);
            $this->body = $this->decodeBody($body);
        }
    }

    /**
     * @inheritdoc
     */
    public function getProperty(string $key)
    {
        return $this->properties->{$key} ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): BagProperties
    {
        return $this->properties;
    }

    /**
     * @inheritdoc
     */
    public function getBinding(string $key)
    {
        return $this->bindings->{$key} ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getBindings(): BagBindings
    {
        return $this->bindings;
    }

    /**
     * @inheritdoc
     */
    public function getRoutingKey(): ?string
    {
        return $this->bindings->routingKey ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function setHeader(string $name, $value): self
    {
        $this->properties->application_headers[$name] = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setHeaders(array $headers): self
    {
        $this->properties->application_headers = array_merge(
            $this->properties->application_headers,
            $headers
        );
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setBody(
        $body,
        string $contentType = self::DEFAULT_CONTENT_TYPE,
        string $contentEncoding = self::DEFAULT_CONTENT_ENCODING
    ): self {
        $this->properties->content_type = $contentType;
        $this->properties->content_encoding = $contentEncoding;
        $this->body = $body;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toAmqpMessage(): AMQPMessage
    {
        $messageProperties = $this->properties->toArray();
        if (!empty($messageProperties["application_headers"])) {
            $messageProperties["application_headers"] = new AMQPTable($messageProperties["application_headers"]);
        }
        return new AMQPMessage($this->encodeBody(), $messageProperties);
    }

    /**
     * @return string
     */
    private function encodeBody(): string
    {
        $encodedBody = '';
        switch ($this->properties->content_type) {
            case self::TEXT_CONTENT_TYPE:
                if (!is_string($this->body)) {
                    throw new MessageBagFormatException(self::BODY_FORMAT_MISMATCH_MESSAGE);
                }
                $encodedBody = $this->body;
                break;
            case self::JSON_CONTENT_TYPE:
                if (!is_array($this->body) && !is_object($this->body)) {
                    throw new MessageBagFormatException(self::BODY_FORMAT_MISMATCH_MESSAGE);
                }
                $encodedBody = json_encode($this->body);
                break;
            case self::GZIP_CONTENT_TYPE:
                if (!is_string($this->body) && !is_array($this->body) && !is_object($this->body)) {
                    throw new MessageBagFormatException(self::BODY_FORMAT_MISMATCH_MESSAGE);
                }
                if (is_array($this->body) || is_object($this->body)) {
                    $this->body = json_encode($this->body);
                }
                $encodedBody = base64_encode(gzcompress($this->body));
                break;
            default:
                throw new MessageBagFormatException(self::UNKNOWN_CONTENT_FORMAT_TYPE_MESSAGE);
        }
        return $encodedBody;
    }

    /**
     * @param $body
     *
     * @return mixed|string|false
     *
     * @throws JsonException
     * @throws MessageBagFormatException
     */
    private function decodeBody($body)
    {
        $decodedBody = $body;
        switch ($this->properties->content_type) {
            case self::JSON_CONTENT_TYPE:
                if (!is_string($decodedBody)) {
                    throw new MessageBagFormatException(self::BODY_FORMAT_MISMATCH_MESSAGE);
                }
                $decodedBody = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
                break;
            case self::GZIP_CONTENT_TYPE:
                try {
                    $decodedBody = gzuncompress(base64_decode($body));
                } catch (Throwable $reason) {
                    throw new MessageBagFormatException(self::BODY_FORMAT_MISMATCH_MESSAGE);
                }
                break;
            case self::TEXT_CONTENT_TYPE:
                if (!is_string($decodedBody)) {
                    throw new MessageBagFormatException(self::BODY_FORMAT_MISMATCH_MESSAGE);
                }
                break;
            default:
                throw new MessageBagFormatException(self::UNKNOWN_CONTENT_FORMAT_TYPE_MESSAGE);
        }
        return $decodedBody;
    }

    /**
     * @param array $properties
     *
     * @return BagProperties
     */
    private function fulfillProperties(array $properties): BagProperties
    {
        // content_type
        !isset($properties["content_type"]) && $this->setDefaultContentType($properties);
        // content_encoding
        !isset($properties["content_encoding"]) && $this->setDefaultContentEncoding($properties);
        // priority
        !isset($properties["priority"]) && $this->setDefaultPriority($properties);
        // correlation_id
        !isset($properties["correlation_id"]) && $this->setDefaultCorrelationId($properties);
        // message_id
        !isset($properties["message_id"]) && $this->setDefaultMessageId($properties);
        // delivery_mode
        !isset($properties["delivery_mode"]) && $this->setDefaultDeliveryMode($properties);
        // type
        !isset($properties["type"]) && $this->setDefaultType($properties);
        // application_headers
        $this->setDefaultApplicationHeaders($properties);

        return new BagProperties($properties);
    }

    /**
     * @param array $properties
     *
     * @return BagProperties
     */
    private function decodeApplicationHeaders(array $properties): BagProperties
    {
        if (!empty($properties["application_headers"]) && $properties["application_headers"] instanceof AMQPTable) {
            $properties["application_headers"] = $properties["application_headers"]->getNativeData();
        }

        return new BagProperties($properties);
    }

    private function setDefaultContentType(&$properties)
    {
        $properties["content_type"] = self::DEFAULT_CONTENT_TYPE;
    }

    private function setDefaultContentEncoding(&$properties)
    {
        $properties["content_encoding"] = self::DEFAULT_CONTENT_ENCODING;
    }

    private function setDefaultPriority(&$properties): void
    {
        $properties["priority"] = self::DEFAULT_PRIORITY;
    }

    private function setDefaultCorrelationId(&$properties): void
    {
        $properties["correlation_id"] = (Uuid::uuid4())->toString();
    }

    private function setDefaultMessageId(&$properties): void
    {
        $properties["message_id"] = (Uuid::uuid4())->toString();
    }

    private function setDefaultType(&$properties): void
    {
        $properties["type"] = self::DEFAULT_TYPE;
    }

    private function setDefaultDeliveryMode(&$properties)
    {
        $properties["delivery_mode"] = self::DEFAULT_DELIVERY_MODE;
    }

    private function setDefaultApplicationHeaders(&$properties): void
    {
        $properties["application_headers"] = $properties["application_headers"] ?? [];
        $properties["application_headers"]['version'] = self::DEFAULT_VERSION;
        $properties["application_headers"]['dateTime'] = (new DateTime('now'))
            ->format(self::DEFAULT_DATETIME_FORMAT);
    }
}
