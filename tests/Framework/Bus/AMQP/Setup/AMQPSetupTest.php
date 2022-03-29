<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Bus\AMQP\Setup;

use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\ContractParser;
use Chassis\Framework\AsyncApi\ContractValidator;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractValidatorException;
use Chassis\Framework\AsyncApi\Transformers\AMQPTransformer;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnector;
use Chassis\Framework\Bus\AMQP\Setup\AMQPSetup;
use ChassisTests\Traits\FixtureConfigurationLoaderTrait;
use Exception;
use Opis\JsonSchema\Validator;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AMQPSetupTest extends TestCase
{
    use FixtureConfigurationLoaderTrait;

    private AMQPConnector $connector;
    private AMQPChannel $amqpChannel;
    private AsyncContract $asyncContract;
    private AMQPSetup $sut;

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     * @throws AsyncContractValidatorException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // mocks
        $this->connector = $this->getMockBuilder(AMQPConnector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getChannel'])
            ->getMock();
        $this->amqpChannel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'close',
                'exchange_bind',
                'exchange_declare',
                'queue_bind',
                'queue_declare',
                'queue_purge',
                'is_open'
            ])->getMock();

        // concretes
        $this->asyncContract = new AsyncContract(
            new ContractParser(),
            new ContractValidator(new Validator())
        );
        $this->asyncContract->setConfiguration($this->loadFixtureConfiguration('brokerForAmqpSetup'));
        $this->asyncContract->pushTransformer(new AMQPTransformer());
        $logger = new NullLogger();

        // system under tests
        $this->sut = new AMQPSetup($this->connector, $this->asyncContract, $logger);
    }

    /**
     * @return void
     */
    public function testSutCanPurgeQueue(): void
    {
        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->amqpChannel);

        $this->amqpChannel->expects($this->once())
            ->method('queue_purge')
            ->with('DaWaPackTests.Q.Commands');
        $this->amqpChannel->expects($this->once())
            ->method('is_open')
            ->willReturn(true);
        $this->amqpChannel->expects($this->once())
            ->method('close');

        $this->assertTrue($this->sut->purge('inbound/commands'));
    }

    /**
     * @return void
     */
    public function testSutMustBeFaultTolerantPurgingAnExchange(): void
    {
        $this->assertFalse($this->sut->purge('outbound/commands'));
    }

    /**
     * @return void
     */
    public function testSutMustBeFaultTolerantPurgingAnUnknownChannel(): void
    {
        $this->assertFalse($this->sut->purge('unknown/channel'));
    }

    /**
     * @return void
     */
    public function testSutMustPropagateAnySetupExceptions(): void
    {
        $this->expectException(Exception::class);

        $this->connector->expects($this->once())
            ->method('getChannel')
            ->willThrowException(new Exception("nobody cares, just throw an exception"));

        $this->sut->setup();
    }

    /**
     * @return void
     */
    public function testSutCanDeclareChannelsWithPassiveOptionSetToTrue(): void
    {
        $this->connector->expects($this->exactly(10))
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->exactly(10))
            ->method('is_open')
            ->willReturn(true);
        $this->amqpChannel->expects($this->exactly(10))
            ->method('close');
        $this->amqpChannel->expects($this->exactly(6))
            ->method('exchange_declare')
            ->will($this->returnCallback(function () {
                $funcArgs = func_get_args();
                if ($funcArgs[2]) {
                    throw new AMQPProtocolChannelException(
                        0, "exception for channel declare with passive true", []
                    );
                }
            }));
        $this->amqpChannel->expects($this->exactly(2))
            ->method('queue_declare')
            ->will($this->returnCallback(function () {
                $funcArgs = func_get_args();
                if ($funcArgs[1]) {
                    throw new AMQPProtocolChannelException(
                        0, "exception for channel declare with passive true", []
                    );
                }
            }));
        $this->amqpChannel->expects($this->once())
            ->method('queue_bind');
        $this->amqpChannel->expects($this->once())
            ->method('exchange_bind');

        $this->sut->setup();
    }

    /**
     * @return void
     */
    public function testSutCanDeclareChannelsWithPassiveOptionSetToFalse(): void
    {
        $this->connector->expects($this->exactly(6))
            ->method('getChannel')
            ->willReturn($this->amqpChannel);
        $this->amqpChannel->expects($this->exactly(6))
            ->method('is_open')
            ->willReturn(true);
        $this->amqpChannel->expects($this->exactly(6))
            ->method('close');
        $this->amqpChannel->expects($this->exactly(3))
            ->method('exchange_declare');
        $this->amqpChannel->expects($this->once())
            ->method('queue_declare');
        $this->amqpChannel->expects($this->once())
            ->method('queue_bind');
        $this->amqpChannel->expects($this->once())
            ->method('exchange_bind');

        $this->sut->setup();
    }
}
