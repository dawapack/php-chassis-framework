<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Adapters\Message;

use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\Bus\AMQP\Message\AMQPMessageBus;
use Chassis\Framework\Bus\Exceptions\MessageBusException;
use ChassisTests\Traits\AMQPMessageTrait;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class InboundMessageTest extends TestCase
{
    use AMQPMessageTrait;

    private AMQPMessageBus $messageBus;
    private InboundMessage $sut;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->messageBus = $this->getMockBuilder(AMQPMessageBus::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBody', 'getHeaders', 'getHeader', 'getProperties', 'getProperty'])
            ->getMock();

        $this->sut = new InboundMessage($this->messageBus);
    }

    /**
     * @return void
     *
     * @throws MessageBusException
     * @throws JsonException
     */
    public function testSutCanGetBody(): void
    {
        $body = '{"my":"body"}';
        $this->messageBus->expects($this->once())
            ->method('getBody')
            ->willReturn(json_decode($body, true));

        $this->sut->setMessage($this->messageBus);
        $responseBody = $this->sut->getBody();
        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey("my", $responseBody);
    }

    /**
     * @return void
     */
    public function testSutCanGetMessageHeaders(): void
    {
        $this->messageBus->expects($this->once())
            ->method('getHeaders')
            ->willReturn(["jobId" => Uuid::uuid4()->toString()]);

        $this->sut->setMessage($this->messageBus);
        $responseHeaders = $this->sut->getHeaders();
        $this->assertIsArray($responseHeaders);
        $this->assertArrayHasKey("jobId", $responseHeaders);
    }

    /**
     * @return void
     */
    public function testSutCanGetOneHeader(): void
    {
        $jobId = Uuid::uuid4()->toString();
        $this->messageBus->expects($this->once())
            ->method('getHeaders')
            ->willReturn(["jobId" => $jobId]);

        $this->sut->setMessage($this->messageBus);
        $this->assertEquals($jobId, $this->sut->getHeader("jobId"));
    }

    /**
     * @return void
     */
    public function testSutCanGetMessageProperties(): void
    {
        $properties = $this->createAMQPMessageProperties();
        $this->messageBus->expects($this->once())
            ->method('getProperties')
            ->willReturn($properties);

        $this->sut->setMessage($this->messageBus);
        $this->assertEquals($properties, $this->sut->getProperties());
    }

    /**
     * @return void
     */
    public function testSutCanGetOneProperty(): void
    {
        $properties = $this->createAMQPMessageProperties();
        $property = $properties["correlation_id"];
        $this->messageBus->expects($this->once())
            ->method('getProperties')
            ->willReturn($properties);

        $this->sut->setMessage($this->messageBus);
        $this->assertEquals($property, $this->sut->getProperty("correlation_id"));
    }
}
