<?php

declare(strict_types=1);

namespace ChassisTests\Framework\AsyncApi\Transformers;

use Chassis\Framework\AsyncApi\AsyncContract;
use Chassis\Framework\AsyncApi\ContractParser;
use Chassis\Framework\AsyncApi\ContractValidator;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractValidatorException;
use Chassis\Framework\AsyncApi\Transformers\AMQPTransformer;
use ChassisTests\Traits\FixtureConfigurationLoaderTrait;
use Exception;
use Opis\JsonSchema\Validator;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class AMQPTransformerTest extends TestCase
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
    public function testSutCanReturnAmqpStreamConnectionFunctionArguments(): void
    {
        $arguments = $this->sut
            ->getTransformer()
            ->toConnectionArguments(false);

        $this->assertIsArray($arguments);
        $this->assertEquals("chassis_rabbitmq", $arguments["host"]);
    }

    /**
     * @return void
     */
    public function testSutCanReturnPublishFunctionArguments(): void
    {
        $channel = "outbound/commands";
        $arguments = $this->sut
            ->transform($channel)
            ->toPublishArguments(
                new AMQPMessage('{"unit":"tests"}', []),
                'DaWaPackTests.RK.Commands',
                false
            );

        $this->assertIsArray($arguments);
        $this->assertEquals($this->sut->getChannelBindings($channel)->name, $arguments["exchange"]);
    }

    /**
     * @return void
     */
    public function testSutCanReturnConsumeFunctionArguments(): void
    {
        $channel = "inbound/commands";
        $arguments = $this->sut
            ->transform($channel)
            ->toConsumeArguments([], false);

        $this->assertIsArray($arguments);
        $this->assertEquals($this->sut->getChannelBindings($channel)->name, $arguments["queue"]);
    }
}
