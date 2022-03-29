<?php

declare(strict_types=1);

namespace ChassisTests\Framework\AsyncApi;

use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\ContractParser;
use Chassis\Framework\AsyncApi\ContractValidator;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractValidatorException;
use Chassis\Framework\AsyncApi\Transformers\AMQPTransformer;
use ChassisTests\Traits\FixtureConfigurationLoaderTrait;
use Exception;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class AsyncContractTest extends TestCase
{
    use FixtureConfigurationLoaderTrait;

    private AsyncContract $sut;

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

        $this->sut = new AsyncContract(
            new ContractParser(),
            new ContractValidator(
                new Validator()
            )
        );

        $this->sut->setConfiguration(
            $this->loadFixtureConfiguration("broker")
        );
        $this->sut->pushTransformer(new AMQPTransformer());
    }

    /**
     * @return void
     */
    public function testSutCanReturnChannelBindings(): void
    {
        $channelBindings = $this->sut->getChannelBindings("outbound/commands");
        $this->assertIsObject($channelBindings);
        $this->assertEquals("DaWaPackTests.DX.Commands", $channelBindings->name);
    }

    /**
     * @return void
     */
    public function testSutCanReturnOperationBindings(): void
    {
        $operationBindings = $this->sut->getOperationBindings("outbound/commands");
        $this->assertIsObject($operationBindings);
        $this->assertFalse($operationBindings->mandatory);
    }

    /**
     * @return void
     */
    public function testSutCanReturnMessageBindings(): void
    {
        $messageBindings = $this->sut->getMessageBindings("outbound/commands");
        $this->assertIsObject($messageBindings);
        $this->assertEquals("#any", $messageBindings->messageType);
    }

    /**
     * @return void
     */
    public function testSutCanReturnChannelsList(): void
    {
        $channels = $this->sut->getChannels();
        $this->assertIsArray($channels);
        $this->assertTrue(count($channels) > 0);
    }

    /**
     * @return void
     */
    public function testSutCanReturnAmqpTransformerInstance(): void
    {
        $this->assertInstanceOf(
            AMQPTransformer::class,
            $this->sut->transform("inbound/commands", [])
        );
    }
}
