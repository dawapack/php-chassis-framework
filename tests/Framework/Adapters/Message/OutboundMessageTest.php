<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Adapters\Message;

use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Bus\AMQP\Message\AMQPMessageBus;
use Chassis\Framework\Bus\AMQP\Message\Exceptions\MessageBodyContentTypeException;
use ChassisTests\Traits\AMQPMessageTrait;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class OutboundMessageTest extends TestCase
{
    use AMQPMessageTrait;

    private AMQPMessageBus $messageBus;
    private OutboundMessage $sut;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->messageBus = $this->getMockBuilder(AMQPMessageBus::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['convert'])
            ->getMock();

        $this->sut = new OutboundMessage($this->messageBus);
    }

    /**
     * @return void
     *
     * @throws MessageBodyContentTypeException
     */
    public function testSutCanCreateAnOutboundMessage(): void
    {
        $jobId = Uuid::uuid4()->toString();
        $correlationId = Uuid::uuid4()->toString();
        $body = ["my" => "body"];
        $properties = $this->createAMQPMessageProperties();
        $headers = ["statusCode" => 200, "statusMessage" => "DONE"];

        $this->sut->setBody($body);
        $this->sut->setProperties($properties);
        $this->sut->setProperty("correlation_id", $correlationId);
        $this->sut->setHeaders(["statusCode" => 200, "statusMessage" => "DONE"]);
        $this->sut->setHeader("jobId", $jobId);

        $headers["jobId"] = $jobId;
        $properties["application_headers"] = new AMQPTable($headers);
        $properties["correlation_id"] = $correlationId;
        $amqpMessage = new AMQPMessage($body, $properties);
        unset($properties["application_headers"]);

        $this->messageBus->expects($this->once())
            ->method("convert")
            ->with($body, $properties, $headers)
            ->willReturn($amqpMessage);

        $this->assertEquals($amqpMessage, $this->sut->toMessageBus());
    }
}
