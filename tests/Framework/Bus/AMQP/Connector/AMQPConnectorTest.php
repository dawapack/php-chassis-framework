<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Bus\AMQP\Connector;

use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\ContractParser;
use Chassis\Framework\AsyncApi\ContractValidator;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractValidatorException;
use Chassis\Framework\AsyncApi\Transformers\AMQPTransformer;
use Chassis\Framework\Bus\AMQP\Connector\AMQPConnector;
use ChassisTests\Traits\FixtureConfigurationLoaderTrait;
use Exception;
use Opis\JsonSchema\Validator;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPIOException;
use PHPUnit\Framework\TestCase;

class AMQPConnectorTest extends TestCase
{
    use FixtureConfigurationLoaderTrait;

    private AMQPConnector $sut;

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

        $configuration = $this->loadFixtureConfiguration("broker");

        $asyncContract = new AsyncContract(
            new ContractParser(),
            new ContractValidator(new Validator())
        );

        // change heartbeat interval
        $configuration["connections"][$configuration["connection"]]["heartbeat"] = 2;
        $asyncContract->setConfiguration($configuration);
        $asyncContract->pushTransformer(new AMQPTransformer());

        $this->sut = new AMQPConnector($asyncContract);
    }

    /**
     * @return void
     */
    public function testSutCanConnectToServerBusAndReturnAChannel(): void
    {
        $this->assertInstanceOf(AMQPChannel::class, $this->sut->getChannel());
    }

    /**
     * @return void
     *
     * @throws AMQPIOException
     */
    public function testSutCanHandleHeartbeatCheck(): void
    {
        usleep(1100000);
        $this->sut->checkHeartbeat();
        $this->assertTrue(true);
    }

    /**
     * @return void
     *
     * @throws AMQPIOException
     */
    public function testSutExitsHeartbeatCheckIfNotConnected(): void
    {
        $this->sut->disconnect();
        $this->sut->checkHeartbeat();
        $this->assertTrue(true);
    }
}
