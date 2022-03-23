<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Bus\AMQP\Message;

use Chassis\Framework\Bus\AMQP\Message\AMQPMessageBus;
use Chassis\Framework\Bus\Exceptions\MessageBusException;
use ChassisTests\Traits\AMQPMessageTrait;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class AMQPMessageBusTest extends TestCase
{
    use AMQPMessageTrait;

    private AMQPMessageBus $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new AMQPMessageBus();
    }

    /**
     * @return void
     */
    public function testSutCanSetAnAmqpMessage(): void
    {
        $messageProperties = $this->createAMQPMessageProperties();
        $messageProperties["application_headers"] = new AMQPTable(["jobId" => Uuid::uuid4()->toString()]);
        $messageBus = new AMQPMessage('{"unit":"tests"}', $messageProperties);
        $messageProperties["application_headers"] = $messageProperties["application_headers"]->getNativeData();

        $this->sut->setMessage($messageBus);
        $this->assertEquals($messageProperties, $this->sut->getProperties());
    }

    /**
     * @return void
     *
     * @throws MessageBusException
     * @throws JsonException
     */
    public function testSutCanSetAnAmqpMessageHavingJsonContentTypeAndReturnTheBody(): void
    {
        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::JSON_CONTENT_TYPE, []);
        $originalBody = '{"unit":"tests"}';
        $messageBus = new AMQPMessage($originalBody, $messageProperties);
        $this->sut->setMessage($messageBus);

        $body = $this->sut->getBody();
        $this->assertIsArray($body);
        $this->assertArrayHasKey("unit", $body);
    }

    /**
     * @return void
     *
     * @throws MessageBusException
     * @throws JsonException
     */
    public function testSutCanSetAnAmqpMessageHavingTextContentTypeAndReturnTheBody(): void
    {
        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::TEXT_CONTENT_TYPE, []);
        $originalBody = "unit tests";
        $messageBus = new AMQPMessage($originalBody, $messageProperties);
        $this->sut->setMessage($messageBus);

        $body = $this->sut->getBody();
        $this->assertIsString($body);
        $this->assertEquals($originalBody, $body);
    }

    /**
     * @return void
     *
     * @throws MessageBusException
     * @throws JsonException
     */
    public function testSutCanSetAnAmqpMessageHavingGzipContentTypeAndReturnTheBody(): void
    {
        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::GZIP_CONTENT_TYPE, []);
        $originalBody = base64_encode(gzcompress('{"unit":"tests"}'));
        $messageBus = new AMQPMessage($originalBody, $messageProperties);
        $this->sut->setMessage($messageBus);

        $body = $this->sut->getBody();
        $this->assertIsArray($body);
        $this->assertArrayHasKey("unit", $body);
    }

    /**
     * @return void
     *
     * @throws MessageBusException
     */
    public function testSutCanConvertJsonBodyFormatAndCustomPropertiesDataIntoAnAmqpMessage(): void
    {
        $jobId = Uuid::uuid4()->toString();

        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::JSON_CONTENT_TYPE);
        $applicationHeaders = ["jobId" => $jobId];
        $originalBody = ["unit" => "tests"];
        $amqpMessage = $this->sut->convert($originalBody, $messageProperties, $applicationHeaders);

        // need to encode application headers in order to assert is equal
        $messageProperties["application_headers"] = new AMQPTable($applicationHeaders);
        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($messageProperties, $amqpMessage->get_properties());
        $this->assertEquals(json_encode($originalBody), $amqpMessage->getBody());
    }

    /**
     * @return void
     *
     * @throws MessageBusException
     */
    public function testSutCanConvertTextBodyFormatAndCustomPropertiesDataIntoAnAmqpMessage(): void
    {
        $jobId = Uuid::uuid4()->toString();

        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::TEXT_CONTENT_TYPE);
        $applicationHeaders = ["jobId" => $jobId];
        $originalBody = "this is a text";
        $amqpMessage = $this->sut->convert($originalBody, $messageProperties, $applicationHeaders);

        // need to encode application headers in order to assert is equal
        $messageProperties["application_headers"] = new AMQPTable($applicationHeaders);
        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($messageProperties, $amqpMessage->get_properties());
        $this->assertEquals($originalBody, $amqpMessage->getBody());
    }

    /**
     * @return void
     *
     * @throws MessageBusException
     */
    public function testSutCanConvertGzipBodyFormatAndCustomPropertiesDataIntoAnAmqpMessage(): void
    {
        $jobId = Uuid::uuid4()->toString();

        // with application headers as AMQPTable object
        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::GZIP_CONTENT_TYPE);
        $applicationHeaders = ["jobId" => $jobId];
        $originalBody = json_encode(["unit" => "tests"]);
        $amqpMessage = $this->sut->convert($originalBody, $messageProperties, $applicationHeaders);

        // need to encode application headers in order to assert is equal
        $messageProperties["application_headers"] = new AMQPTable($applicationHeaders);
        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($messageProperties, $amqpMessage->get_properties());
        $this->assertEquals(base64_encode(gzcompress(json_encode($originalBody))), $amqpMessage->getBody());
    }

    /**
     * @return void
     */
    public function testSutCanReturnPropertiesWithApplicationHeaders(): void
    {
        $jobId = Uuid::uuid4()->toString();

        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::JSON_CONTENT_TYPE);
        $messageProperties["application_headers"] = new AMQPTable(["jobId" => $jobId]);
        $originalBody = '{"unit":"tests"}';
        $messageBus = new AMQPMessage($originalBody, $messageProperties);
        $this->sut->setMessage($messageBus);

        $this->assertIsArray($this->sut->getHeaders());
        $this->assertEquals($jobId, $this->sut->getHeader("jobId"));
    }

    /**
     * @return void
     */
    public function testSutCanReturnNullIfRequestedPropertyNotFound(): void
    {
        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::JSON_CONTENT_TYPE);
        $originalBody = '{"unit":"tests"}';
        $messageBus = new AMQPMessage($originalBody, $messageProperties);
        $this->sut->setMessage($messageBus);

        $this->assertNull($this->sut->getProperty("unknown_property"));
    }

    /**
     * @return void
     */
    public function testSutMustThrowMessageBodyContentTypeExceptionWithTextContentTypeAndArrayBodyFormat(): void
    {
        $this->expectException(MessageBusException::class);

        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::TEXT_CONTENT_TYPE);
        $originalBody = ["unit" => "tests"];

        $this->sut->convert($originalBody, $messageProperties, ["jobId" => Uuid::uuid4()->toString()]);
    }

    /**
     * @return void
     */
    public function testSutMustThrowMessageBodyContentTypeExceptionWithJsonContentTypeAndTextBodyFormat(): void
    {
        $this->expectException(MessageBusException::class);

        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::JSON_CONTENT_TYPE);
        $originalBody = "this string is not allowed for json content type";

        $this->sut->convert($originalBody, $messageProperties, ["jobId" => Uuid::uuid4()->toString()]);
    }

    /**
     * @return void
     */
    public function testSutMustThrowMessageBodyContentTypeExceptionWithGzipContentTypeAndBooleanBodyFormat(): void
    {
        $this->expectException(MessageBusException::class);

        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::GZIP_CONTENT_TYPE);
        $originalBody = false;

        $this->sut->convert($originalBody, $messageProperties, ["jobId" => Uuid::uuid4()->toString()]);
    }

    /**
     * @return void
     */
    public function testSutMustThrowMessageBodyContentTypeExceptionWithUnknownContentType(): void
    {
        $this->expectException(MessageBusException::class);
        $messageProperties = $this->createAMQPMessageProperties("application/unknown");

        $this->sut->convert(false, $messageProperties, ["jobId" => Uuid::uuid4()->toString()]);
    }

    /**
     * @return void
     *
     * @throws JsonException
     * @throws MessageBusException
     */
    public function testSutMustThrowMessageBodyContentTypeExceptionOnDecodingUnexpectedGzipBodyFormat(): void
    {
        $this->expectException(MessageBusException::class);

        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::GZIP_CONTENT_TYPE);
        $originalBody = "this is not a valid gzip encoded format";
        $this->sut->setMessage(new AMQPMessage($originalBody, $messageProperties));

        $this->sut->getBody();
    }

    /**
     * @return void
     *
     * @throws JsonException
     * @throws MessageBusException
     */
    public function testSutMustThrowJsonExceptionOnDecodingUnexpectedJsonBodyFormat(): void
    {
        $this->expectException(JsonException::class);

        $messageProperties = $this->createAMQPMessageProperties(AMQPMessageBus::JSON_CONTENT_TYPE);
        $originalBody = "this is not a valid json encoded format";
        $this->sut->setMessage(new AMQPMessage($originalBody, $messageProperties));

        $this->sut->getBody();
    }

    /**
     * @return void
     *
     * @throws JsonException
     */
    public function testSutMustThrowMessageBodyContentTypeExceptionOnDecodingUnhandledContentType(): void
    {
        $this->expectException(MessageBusException::class);

        $messageProperties = $this->createAMQPMessageProperties("application/unknown");
        $originalBody = "[]";
        $this->sut->setMessage(new AMQPMessage($originalBody, $messageProperties));

        $this->sut->getBody();
    }
}
