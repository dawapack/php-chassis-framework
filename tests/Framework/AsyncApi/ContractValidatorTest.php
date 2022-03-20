<?php

declare(strict_types=1);

namespace ChassisTests\Framework\AsyncApi;

use Chassis\Framework\AsyncApi\ContractParser;
use Chassis\Framework\AsyncApi\ContractValidator;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractValidatorException;
use ChassisTests\Traits\FixtureConfigurationLoaderTrait;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class ContractValidatorTest extends TestCase
{
    use FixtureConfigurationLoaderTrait;

    private string $protocol = 'amqp';
    private ContractParser $contractParser;
    private ContractValidator $sut;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->contractParser = (new ContractParser())
            ->setConfiguration(
                $this->loadFixtureConfiguration("broker")
            );

        $this->sut = new ContractValidator(new Validator());
    }

    /**
     * @return void
     *
     * @throws AsyncContractValidatorException
     * @throws AsyncContractParserException
     */
    public function testSutCanValidateAParsedContract(): void
    {
        $this->contractParser->parse();
        $this->sut->validate(
            $this->contractParser->getContract(),
            $this->contractParser->getValidatorsPath(),
            $this->protocol
        );
        $this->assertTrue($this->sut->isValid());
    }

    /**
     * @return void
     *
     * @throws AsyncContractValidatorException
     * @throws AsyncContractParserException
     */
    public function testSutMustFailWithAsyncContractValidatorException(): void
    {
        $this->expectException(AsyncContractValidatorException::class);

        $configuration = require __DIR__ . "/../../Fixtures/Configurations/brokerWrongPathsValidator.php";
        $contractParser = (new ContractParser())->setConfiguration($configuration);
        $contractParser->parse();

        $this->sut->validate(
            $contractParser->getContract(),
            $contractParser->getValidatorsPath(),
            $this->protocol
        );
    }

    /**
     * @return void
     *
     * @throws AsyncContractValidatorException
     * @throws AsyncContractParserException
     */
    public function testSutMustFailBecauseDeliveringSchemaFileIsMandatory(): void
    {
        $this->expectException(AsyncContractValidatorException::class);

        $configuration = require __DIR__ . "/../../Fixtures/Configurations/brokerWithoutChannelSchema.php";
        $contractParser = (new ContractParser())->setConfiguration($configuration);
        $contractParser->parse();

        $this->sut->validate(
            $contractParser->getContract(),
            $contractParser->getValidatorsPath(),
            $this->protocol
        );
    }

    /**
     * @return void
     *
     * @throws AsyncContractValidatorException
     * @throws AsyncContractParserException
     */
    public function testSutMustFailOnInvalidContract(): void
    {
        $this->expectException(AsyncContractValidatorException::class);

        $configuration = require __DIR__ . "/../../Fixtures/Configurations/brokerWithInvalidContract.php";
        $contractParser = (new ContractParser())->setConfiguration($configuration);
        $contractParser->parse();

        $this->sut->validate(
            $contractParser->getContract(),
            $contractParser->getValidatorsPath(),
            $this->protocol
        );
    }
}
