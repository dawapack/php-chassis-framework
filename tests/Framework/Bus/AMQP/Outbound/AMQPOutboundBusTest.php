<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Bus\AMQP\Outbound;

use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\Transformers\AMQPTransformer;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnector;
use Chassis\Framework\Bus\AMQP\Message\AMQPMessageBus;
use Chassis\Framework\Bus\AMQP\Message\Exceptions\MessageBodyContentTypeException;
use Chassis\Framework\Bus\AMQP\Outbound\AMQPOutboundBus;
use ChassisTests\Traits\AMQPMessageTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Throwable;

class AMQPOutboundBusTest extends TestCase
{
    use AMQPMessageTrait;

    private AMQPOutboundBus $sut;
    private OutboundMessage $message;
    private AMQPConnector $connector;
    private AMQPChannel $amqpChannel;
    private AsyncContract $asyncContract;
    private AMQPTransformer $transformer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = $this->getMockBuilder(AMQPConnector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChannel'])
            ->getMock();
        $this->amqpChannel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'basic_publish',
                'confirm_select',
                'close',
                'is_open',
                'set_ack_handler',
                'set_nack_handler',
                'wait_for_pending_acks'
            ])
            ->getMock();
        $this->asyncContract = $this->getMockBuilder(AsyncContract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['transform'])
            ->getMock();
        $this->transformer = $this->getMockBuilder(AMQPTransformer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toPublishArguments'])
            ->getMock();

        $this->sut = new AMQPOutboundBus(
            $this->connector,
            $this->asyncContract,
            new NullLogger()
        );
    }

    /**
     * @return void
     *
     * @throws MessageBodyContentTypeException
     * @throws Throwable
     */
    public function testSutCanPublish(): void
    {
        $channel = "outbound/commands";
        $routingKey = "DaWaPackTests.RK.Commands";

        // create outbound message
        $message = (new OutboundMessage(new AMQPMessageBus()))
            ->setProperties($this->createAMQPMessageProperties())
            ->setHeader("jobId", Uuid::uuid4()->toString())
            ->setBody(["unit" => "tests"]);

        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->once())
            ->method('set_ack_handler');
        $this->amqpChannel->expects($this->once())
            ->method('set_nack_handler');
        $this->amqpChannel->expects($this->once())
            ->method('confirm_select');
        $this->asyncContract->expects($this->once())
            ->method('transform')
            ->with($channel)
            ->willReturn($this->transformer);
        $this->transformer->expects($this->once())
            ->method('toPublishArguments')
            ->with($message->toMessageBus(), $routingKey)
            ->willReturn([
                $message->toMessageBus(),
                "DaWaPackTests.DX.Commands",
                $routingKey,
                false,
                false,
                null
            ]);
        $this->amqpChannel->expects($this->once())
            ->method('basic_publish')
            ->with(
                $message->toMessageBus(),
                "DaWaPackTests.DX.Commands",
                $routingKey,
                false,
                false,
                null
            );
        $this->amqpChannel->expects($this->once())
            ->method('wait_for_pending_acks');
        $this->amqpChannel->expects($this->once())
            ->method('is_open')
            ->willReturn(true);
        $this->amqpChannel->expects($this->once())
            ->method('close');

        $this->sut->publish($message, $channel, $routingKey);
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    public function testSutCanPublishResponse(): void
    {
        $channel = "amqp/default";

        // create outbound & inbound message
        $outboundMessage = (new OutboundMessage(new AMQPMessageBus()))
            ->setProperties($this->createAMQPMessageProperties())
            ->setHeader("jobId", Uuid::uuid4()->toString())
            ->setBody(["outbound" => "message"]);
        $messageBus = new AMQPMessageBus();
        $messageBus->setMessage(
            $this->createAMQPMessage(
                ['inbound' => 'message'],
                ['reply_to' => 'DaWaPackTests.Q.Responses']
            )
        );
        $inboundMessage = new InboundMessage($messageBus);

        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->once())
            ->method('set_ack_handler');
        $this->amqpChannel->expects($this->once())
            ->method('set_nack_handler');
        $this->amqpChannel->expects($this->once())
            ->method('confirm_select');
        $this->asyncContract->expects($this->once())
            ->method('transform')
            ->with($channel)
            ->willReturn($this->transformer);
        $this->transformer->expects($this->once())
            ->method('toPublishArguments')
            ->willReturn([
                $messageBus,
                "DaWaPackTests.DX.Commands",
                "DaWaPackTests.Q.Responses",
                false,
                false,
                null
            ]);
        $this->amqpChannel->expects($this->once())
            ->method('basic_publish')
            ->with(
                $messageBus,
                "DaWaPackTests.DX.Commands",
                "DaWaPackTests.Q.Responses",
                false,
                false,
                null
            );
        $this->amqpChannel->expects($this->once())
            ->method('wait_for_pending_acks');
        $this->amqpChannel->expects($this->once())
            ->method('is_open')
            ->willReturn(true);
        $this->amqpChannel->expects($this->once())
            ->method('close');

        $this->assertNull($this->sut->publishResponse($outboundMessage, $inboundMessage));
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    public function testSutCanNotPublishResponseWithoutReplyToPropertyInInboundMessage(): void
    {
        $channel = "amqp/default";

        // create outbound & inbound message
        $outboundMessage = (new OutboundMessage(new AMQPMessageBus()))
            ->setProperties($this->createAMQPMessageProperties())
            ->setHeader("jobId", Uuid::uuid4()->toString())
            ->setBody(["outbound" => "message"]);
        $messageBus = new AMQPMessageBus();
        $messageBus->setMessage(
            $this->createAMQPMessage(['inbound' => 'message'])
        );
        $inboundMessage = new InboundMessage($messageBus);

        $this->connector->expects($this->never())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);

        $this->assertNull($this->sut->publishResponse($outboundMessage, $inboundMessage));
    }

    /**
     * @return void
     */
    public function testSutThrowAnAmqpTimeoutExceptionOnPublishTimeout(): void
    {
        $this->expectException(AMQPTimeoutException::class);

        $channel = "outbound/commands";
        $routingKey = "DaWaPackTests.RK.Commands";

        // create outbound message
        $message = (new OutboundMessage(new AMQPMessageBus()))
            ->setProperties($this->createAMQPMessageProperties())
            ->setHeader("jobId", Uuid::uuid4()->toString())
            ->setBody(["unit" => "tests"]);

        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->once())
            ->method('set_ack_handler');
        $this->amqpChannel->expects($this->once())
            ->method('set_nack_handler');
        $this->amqpChannel->expects($this->once())
            ->method('confirm_select');
        $this->asyncContract->expects($this->once())
            ->method('transform')
            ->with($channel)
            ->willReturn($this->transformer);
        $this->transformer->expects($this->once())
            ->method('toPublishArguments')
            ->with($message->toMessageBus(), $routingKey)
            ->willReturn([
                $message->toMessageBus(),
                "DaWaPackTests.DX.Commands",
                $routingKey,
                false,
                false,
                null
            ]);
        $this->amqpChannel->expects($this->once())
            ->method('basic_publish')
            ->with(
                $message->toMessageBus(),
                "DaWaPackTests.DX.Commands",
                $routingKey,
                false,
                false,
                null
            );
        $this->amqpChannel->expects($this->once())
            ->method('wait_for_pending_acks')
            ->willThrowException(new AMQPTimeoutException("timeout"));

        $this->sut->publish($message, $channel, $routingKey);
    }
}
