<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Message;

use Chassis\Framework\Bus\Exceptions\MessageBusException;
use Chassis\Framework\Bus\MessageBusInterface;
use JsonException;
use OutOfBoundsException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;
use Throwable;

class AMQPMessageBus implements MessageBusInterface
{
    public const TEXT_CONTENT_TYPE = 'text/plain';
    public const GZIP_CONTENT_TYPE = 'application/gzip';
    public const JSON_CONTENT_TYPE = 'application/json';
    public const DEFAULT_CONTENT_TYPE = self::JSON_CONTENT_TYPE;
    public const DEFAULT_CONTENT_ENCODING = 'UTF-8';
    public const DEFAULT_DELIVERY_MODE = 2;
    public const DEFAULT_PRIORITY = 0;

    private const INVALID_BODY_FORMAT_MESSAGE = 'invalid body format';
    private const INVALID_CONTENT_TYPE_MESSAGE = 'invalid content type';

    private AMQPMessage $message;

    /**
     * @inheritdoc
     */
    public function setMessage(AMQPMessage $messageBus)
    {
        $this->message = $messageBus;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->decodeBody();
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): array
    {
        $properties = $this->message->get_properties();
        if (!empty($properties["application_headers"]) && $properties["application_headers"] instanceof AMQPTable) {
            $properties["application_headers"] = $properties["application_headers"]->getNativeData();
        }

        return $properties;
    }

    /**
     * @inheritdoc
     */
    public function getProperty(string $name)
    {
        try {
            $property = $this->message->get($name);

            return ($property instanceof AMQPTable)
                ? $property->getNativeData()
                : $property;
        } catch (OutOfBoundsException $reason) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getDefaultProperties(): array
    {
        return [
            "content_type" => self::DEFAULT_CONTENT_TYPE,
            "content_encoding" => self::DEFAULT_CONTENT_ENCODING,
            "message_id" => Uuid::uuid4()->toString(),
            "correlation_id" => Uuid::uuid4()->toString(),
            "timestamp" => null,
            "expiration" => null,
            "delivery_mode" => self::DEFAULT_DELIVERY_MODE,
            "app_id" => null,
            "user_id" => null,
            "type" => "noOperation",
            "reply_to" => "",
            "priority" => self::DEFAULT_PRIORITY,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): ?array
    {
        return $this->getProperty("application_headers");
    }

    /**
     * @inheritdoc
     */
    public function getHeader(string $name)
    {
        $headers = $this->getHeaders();

        return (!is_null($headers))
            ? $headers[$name] ?? null
            : null;
    }

    /**
     * @param string|array|object $body
     * @param array $properties
     * @param array $headers
     *
     * @return AMQPMessage
     *
     * @throws MessageBusException
     */
    public function convert($body, array $properties, array $headers): AMQPMessage
    {
        return new AMQPMessage(
            $this->encodeBody($body, $properties["content_type"]),
            $this->createProperties($properties, $headers)
        );
    }

    /**
     * @return string|array
     *
     * @throws JsonException
     * @throws MessageBusException
     */
    protected function decodeBody()
    {
        $originalBody = $this->message->getBody();
        switch ($this->getProperty("content_type")) {
            case self::JSON_CONTENT_TYPE:
                $decodedBody = json_decode($originalBody, true, 64, JSON_THROW_ON_ERROR);
                break;
            case self::GZIP_CONTENT_TYPE:
                try {
                    $uncompressed = gzuncompress(base64_decode($originalBody));
                    $decodedBody = json_decode($uncompressed, true, 64, JSON_THROW_ON_ERROR);
                } catch (Throwable $reason) {
                    throw new MessageBusException(
                        sprintf("%s %s", self::GZIP_CONTENT_TYPE, self::INVALID_BODY_FORMAT_MESSAGE)
                    );
                }
                break;
            case self::TEXT_CONTENT_TYPE:
                $decodedBody = $originalBody;
                break;
            default:
                throw new MessageBusException(self::INVALID_CONTENT_TYPE_MESSAGE);
        }

        return $decodedBody;
    }

    /**
     * @param string|array|object $body
     * @param string $content_type
     *
     * @return string
     *
     * @throws MessageBusException
     */
    private function encodeBody($body, string $content_type): string
    {
        switch ($content_type) {
            case self::TEXT_CONTENT_TYPE:
                if (!is_string($body)) {
                    throw new MessageBusException(
                        sprintf("%s %s", self::JSON_CONTENT_TYPE, self::INVALID_BODY_FORMAT_MESSAGE)
                    );
                }
                $encodedBody = $body;
                break;
            case self::JSON_CONTENT_TYPE:
                if (!is_array($body) && !is_object($body)) {
                    throw new MessageBusException(
                        sprintf("%s %s", self::JSON_CONTENT_TYPE, self::INVALID_BODY_FORMAT_MESSAGE)
                    );
                }
                $encodedBody = json_encode($body);
                break;
            case self::GZIP_CONTENT_TYPE:
                if (!is_string($body) && !is_array($body) && !is_object($body)) {
                    throw new MessageBusException(
                        sprintf("%s %s", self::JSON_CONTENT_TYPE, self::INVALID_BODY_FORMAT_MESSAGE)
                    );
                }
                $encodedBody = base64_encode(gzcompress(json_encode($body)));
                break;
            default:
                throw new MessageBusException(self::INVALID_CONTENT_TYPE_MESSAGE);
        }

        return $encodedBody;
    }

    /**
     * @param array $properties
     * @param array $headers
     *
     * @return array
     */
    protected function createProperties(array $properties, array $headers): array
    {
        if (!empty($headers)) {
            $properties["application_headers"] = new AMQPTable($headers);
        }

        return $properties;
    }
}
