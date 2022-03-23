<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Bus\AMQP\Inbound;

use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnector;
use Chassis\Framework\Bus\AMQP\Inbound\AMQPInboundBus;
use Chassis\Framework\Routers\InboundRouter;
use ChassisTests\Traits\AMQPMessageTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;

/**
 * @note methods messageHandler() and toBasicConsumeArguments(...) will be covered by integration tests
 */
class AMQPInboundBusTest extends TestCase
{
    use AMQPMessageTrait;

    private AMQPChannel $amqpChannel;
    private AMQPConnector $connector;
    private AsyncContract $asyncContract;
    private InboundMessage $inboundMessage;
    private InboundRouter $inboundRouter;
    private AMQPMessage $amqpMessage;
    private AMQPInboundBus $sut;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->amqpChannel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['basic_consume', 'basic_get', 'basic_qos', 'close', 'is_open', 'wait'])
            ->getMock();
        $this->amqpMessage = $this->getMockBuilder(AMQPMessage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ack', 'nack', 'get_properties'])
            ->getMock();

        $this->connector = $this->getMockBuilder(AMQPConnector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkHeartbeat', 'getChannel'])
            ->getMock();
        $this->asyncContract = $this->getMockBuilder(AsyncContract::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inboundMessage = $this->getMockBuilder(InboundMessage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setMessage'])
            ->getMock();
        $this->inboundRouter = $this->getMockBuilder(InboundRouter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = new NullLogger();

        $this->sut = $this->getMockBuilder(AMQPInboundBus::class)
            ->setConstructorArgs([
                $this->connector,
                $this->asyncContract,
                $this->inboundMessage,
                $this->inboundRouter,
                $logger
            ])
            ->onlyMethods(['toBasicConsumeArguments'])
            ->getMock();
    }

    /**
     * @return void
     */
    public function testSutCanConsume(): void
    {
        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->once())
            ->method('basic_qos')
            ->with(0, 1, false);
        $this->amqpChannel->expects($this->once())
            ->method('basic_consume');
        $this->sut->consume("inbound/commands", [], []);
    }

    /**
     * @return void
     */
    public function testSutCanIterate(): void
    {
        $this->testSutCanConsume();

        $this->amqpChannel->expects($this->exactly(2))
            ->method('wait')
            ->with(null, false, AMQPInboundBus::ITERATE_WAIT);
        $this->connector->expects($this->exactly(2))
            ->method('checkHeartbeat');
        $this->sut->iterate();

        $this->amqpChannel->method('wait')
            ->willThrowException(new AMQPTimeoutException());
        $this->sut->iterate();
    }

    /**
     * @return void
     */
    public function testSutCanRunBasicGetAndReturnAnInboundMessageInstance(): void
    {
        $amqpMessageProperties = $this->createAMQPMessageProperties();

        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->once())
            ->method('basic_get')
            ->willReturn($this->amqpMessage);
        $this->amqpMessage->expects($this->once())
            ->method('get_properties')
            ->willReturn($amqpMessageProperties);
        $this->amqpMessage->expects($this->once())
            ->method('ack');
        $this->inboundMessage->expects($this->once())
            ->method('setMessage')
            ->with($this->amqpMessage);
        $this->amqpChannel->expects($this->once())
            ->method('is_open')
            ->willReturn(true);
        $this->amqpChannel->expects($this->once())
            ->method('close');
        $inboundMessage = $this->sut->get(
            "inbound/commands",
            $amqpMessageProperties["correlation_id"],
            1
        );

        $this->assertInstanceOf(InboundMessage::class, $inboundMessage);
    }

    /**
     * @return void
     */
    public function testSutCanRunBasicGetAndReturnNoMessage(): void
    {
        $amqpMessageProperties = $this->createAMQPMessageProperties();

        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->any())
            ->method('basic_get')
            ->willReturn($this->amqpMessage);
        $this->amqpMessage->expects($this->any())
            ->method('get_properties')
            ->willReturn($amqpMessageProperties);
        $this->amqpMessage->expects($this->any())
            ->method('nack');
        $this->amqpChannel->expects($this->once())
            ->method('close');
        $inboundMessage = $this->sut->get(
            "inbound/commands",
            Uuid::uuid4()->toString(),
            1
        );

        $this->assertNull($inboundMessage);
    }

    /**
     * @return void
     */
    public function testSutCanInterceptBasicGetExceptions(): void
    {
        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->any())
            ->method('basic_get')
            ->willReturn($this->amqpMessage);
        $this->amqpMessage->expects($this->any())
            ->method('get_properties')
            ->willThrowException(new \Exception("Behavioural exception"));
        $this->amqpChannel->expects($this->once())
            ->method('is_open')
            ->willReturn(true);
        $this->amqpChannel->expects($this->once())
            ->method('close');
        $inboundMessage = $this->sut->get(
            "inbound/commands",
            Uuid::uuid4()->toString(),
            1
        );

        $this->assertNull($inboundMessage);
    }
}
