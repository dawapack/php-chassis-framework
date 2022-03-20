<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Adapters\Outbound;

use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Adapters\Outbound\OutboundBusAdapter;
use Chassis\Framework\Bus\AMQP\Message\AMQPMessageBus;
use Chassis\Framework\Bus\AMQP\Outbound\AMQPOutboundBus;
use Chassis\Framework\Bus\OutboundBusInterface;
use ChassisTests\Traits\AMQPMessageTrait;
use PHPUnit\Framework\TestCase;

class OutboundBusAdapterTest extends TestCase
{
    use AMQPMessageTrait;

    private OutboundBusAdapter $sut;
    private AMQPOutboundBus $outboundBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outboundBus = $this->getMockBuilder(AMQPOutboundBus::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['publish', 'publishResponse'])
            ->getMock();

        $this->sut = new OutboundBusAdapter($this->outboundBus);
    }

    /**
     * @return void
     */
    public function testSutCanPublish(): void
    {
        $outboundMessage = new OutboundMessage(new AMQPMessageBus());
        $outboundMessage->setProperties($this->createAMQPMessageProperties())
            ->setBody(["my" => "body"]);
        $channel = "outbound/commands";
        $routingKey = "DaWaPackTests.RK.Commands";

        $this->outboundBus->expects($this->once())
            ->method('publish')
            ->with($outboundMessage, $channel, $routingKey);

        $this->sut->push($outboundMessage, $channel, $routingKey);
    }

    /**
     * @return void
     */
    public function testSutCanPublishAResponse(): void
    {
        $messageBus = new AMQPMessageBus();
        $contextMessage = new InboundMessage($messageBus);
        $outboundMessage = new OutboundMessage($messageBus);

        $this->outboundBus->expects($this->once())
            ->method('publishResponse')
            ->with($outboundMessage, $contextMessage)
            ->willReturn(null);

        $this->assertNull($this->sut->pushResponse($outboundMessage, $contextMessage));
    }
}
