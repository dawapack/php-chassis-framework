<?php

declare(strict_types=1);

namespace ChassisTests\Framework\AsyncApi;

use Chassis\Framework\AsyncApi\ContractParser;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use ChassisTests\Traits\FixtureConfigurationLoaderTrait;
use Exception;
use PHPUnit\Framework\TestCase;

class ContractParserTest extends TestCase
{
    use FixtureConfigurationLoaderTrait;

    private ContractParser $sut;

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = (new ContractParser())
            ->setConfiguration(
                $this->loadFixtureConfiguration("broker")
            );
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     */
    public function testSutCanParseAValidContract(): void
    {
        $this->sut->parse();
        $this->assertEquals(
            "/var/package/tests/Fixtures/AsyncApiContracts/json-schemas/bindings/amqp",
            $this->sut->getValidatorsPath()
        );
        $this->assertIsObject($this->sut->getContract());
        $this->assertObjectHasAttribute("channels", $this->sut->getContract());
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     */
    public function testSutMustFailOnGettingFilePath(): void
    {
        $this->expectException(AsyncContractParserException::class);

        $configuration = require __DIR__ . "/../../Fixtures/Configurations/broker.php";
        unset($configuration["contracts"][$configuration["contract"]]);
        $sut = (new ContractParser())->setConfiguration($configuration);
        $sut->parse();
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     * @throws Exception
     */
    public function testSutMustFailOnGettingFilePathBecausePathsSourceIsNotSet(): void
    {
        $this->expectException(AsyncContractParserException::class);

        $configuration = $this->loadFixtureConfiguration("broker");
        unset($configuration["contracts"][$configuration["contract"]]["paths"]);
        $sut = (new ContractParser())->setConfiguration($configuration);
        $sut->parse();
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     * @throws Exception
     */
    public function testSutMustFailOnGettingFilePathBecauseDefinitionsIsNotSet(): void
    {
        $this->expectException(AsyncContractParserException::class);

        $configuration = $this->loadFixtureConfiguration("broker");
        unset($configuration["contracts"][$configuration["contract"]]["definitions"]);
        $sut = (new ContractParser())->setConfiguration($configuration);
        $sut->parse();
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     * @throws Exception
     */
    public function testSutMustFailOnGettingFilePathBecauseSelectedContractIsNotSet(): void
    {
        $this->expectException(AsyncContractParserException::class);

        $configuration = $this->loadFixtureConfiguration("broker");
        unset($configuration["contracts"][$configuration["contract"]]);
        $sut = (new ContractParser())->setConfiguration($configuration);
        $sut->parse();
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     * @throws Exception
     */
    public function testSutMustFailOnGettingFilePathBecausePathValidatorIsNotSet(): void
    {
        $this->expectException(AsyncContractParserException::class);

        $configuration = $this->loadFixtureConfiguration("broker");
        unset($configuration["contracts"][$configuration["contract"]]["paths"]["validator"]);
        $sut = (new ContractParser())->setConfiguration($configuration);
        $sut->parse();
    }
}
