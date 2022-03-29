<?php

declare(strict_types=1);

namespace Chassis\Framework\AsyncApi;

use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ContractParser
{
    private const CONTRACT_SPECIFICATIONS_KEY = 'infrastructure';
    private const MALFORMED_CONFIGURATION_FILE_MESSAGE = 'malformed configuration file';

    private array $configuration;
    private object $parsedContract;
    private string $validatorsPath;

    /**
     * @param array $configuration
     *
     * @return $this
     */
    public function setConfiguration(array $configuration): ContractParser
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     * @throws ParseException
     */
    public function parse(): void
    {
        // set validators path
        !isset($this->validatorsPath) && $this->setValidatorsPath();

        // parse yaml
        $this->parsedContract = Yaml::parseFile(
            $this->getFilePath(self::CONTRACT_SPECIFICATIONS_KEY),
            Yaml::PARSE_OBJECT_FOR_MAP
        );
    }

    /**
     * @return object
     */
    public function getContract(): object
    {
        return $this->parsedContract;
    }

    /**
     * @return object
     */
    public function getValidatorsPath(): string
    {
        return $this->validatorsPath;
    }

    /**
     * @param string $contractType
     *
     * @return string
     *
     * @throws AsyncContractParserException
     */
    protected function getFilePath(string $contractType): string
    {
        $contractData = $this->configuration["contracts"][$this->configuration["contract"]];
        $pathSource = $contractData["paths"]["source"] ?? null;
        $fileName = $contractData["definitions"][$contractType] ?? null;
        if (is_null($pathSource) || is_null($fileName)) {
            throw new AsyncContractParserException(self::MALFORMED_CONFIGURATION_FILE_MESSAGE);
        }

        return $pathSource . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     */
    protected function setValidatorsPath(): void
    {
        $contractData = $this->configuration["contracts"][$this->configuration["contract"]] ?? null;
        if (is_null($contractData)) {
            throw new AsyncContractParserException(self::MALFORMED_CONFIGURATION_FILE_MESSAGE);
        }

        $validatorsPath = $contractData["paths"]["validator"] ?? null;
        if (is_null($validatorsPath)) {
            throw new AsyncContractParserException(self::MALFORMED_CONFIGURATION_FILE_MESSAGE);
        }

        $this->validatorsPath = $validatorsPath;
    }
}
