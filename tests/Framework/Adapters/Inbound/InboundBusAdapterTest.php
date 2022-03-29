<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Adapters\Inbound;

use Chassis\Framework\Adapters\Inbound\Bus\InboundBusAdapter;
use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\Bus\AMQP\Inbound\AMQPInboundBus;
use Chassis\Framework\Bus\AMQP\Message\AMQPMessageBus;
use ChassisTests\Traits\AMQPMessageTrait;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class InboundBusAdapterTest extends TestCase
{
    use AMQPMessageTrait;

    private InboundBusAdapter $sut;
    private AMQPInboundBus $inboundBus;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->inboundBus = $this->getMockBuilder(AMQPInboundBus::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['consume', 'get', 'iterate'])
            ->getMock();

        $this->sut = new InboundBusAdapter($this->inboundBus);
    }

    /**
     * @return void
     */
    public function testSutCanConsume(): void
    {
        $channel = "inbound/commands";
        $options = ["my" => "option"];
        $this->inboundBus->expects($this->once())
            ->method('consume')
            ->with($channel, $options);

        $this->sut->setOptions($options)
            ->subscribe($channel);
    }

    /**
     * @return void
     */
    public function testSutCanPool(): void
    {
        $this->inboundBus->expects($this->once())
            ->method('iterate');

        $this->sut->pool();
    }

    /**
     * @return void
     */
    public function testSutCanGet(): void
    {
        $amqpMessage = $this->createAMQPMessage('{"my":"body_message"}');
        $inboundMessage = new InboundMessage(
            (new AMQPMessageBus())->setMessage($amqpMessage)
        );

        $channel = "inbound/commands";
        $correlationId = $inboundMessage->getProperty("correlation_id");
        $timeout = 5;
        $this->inboundBus->expects($this->once())
            ->method('get')
            ->with($channel, $correlationId, $timeout)
            ->willReturn($inboundMessage);

        $message = $this->sut->get($channel, $correlationId, $timeout);
        $this->assertInstanceOf(InboundMessage::class, $message);
        $this->assertEquals($correlationId, $inboundMessage->getProperty("correlation_id"));
    }

    /**
     * @return void
     */
    public function testSutCanGetNullResponse(): void
    {
        $channel = "inbound/commands";
        $correlationId = Uuid::uuid4()->toString();
        $timeout = 5;
        $this->inboundBus->expects($this->once())
            ->method('get')
            ->with($channel, $correlationId, $timeout)
            ->willReturn(null);

        $this->assertNull($this->sut->get($channel, $correlationId, $timeout));
    }
}
