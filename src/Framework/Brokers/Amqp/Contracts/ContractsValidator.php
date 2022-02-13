<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Contracts;

use Chassis\Framework\Brokers\Amqp\Contracts\Exceptions\ContractsValidatorException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

class ContractsValidator
{
    private const API_URL = 'https://dawapack.api';
    public const CHANNEL = 'channel.json';
    public const OPERATION = 'operation.json';
    public const MESSAGE = 'message.json';

    private Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    public static function getAllSchemaNames(): array
    {
        return [
            self::CHANNEL,
            self::OPERATION,
            self::MESSAGE,
        ];
    }

    public function loadValidators(string $path): void
    {
        if (is_dir($path)) {
            foreach (self::getAllSchemaNames() as $schemaName) {
                $filePath = $path . DIRECTORY_SEPARATOR . $schemaName;
                if (file_exists($filePath)) {
                    $this->validator
                        ->resolver()
                        ->registerFile(self::API_URL . DIRECTORY_SEPARATOR . $schemaName, $filePath);
                }
            }
        }
    }

    /**
     * @throws ContractsValidatorException
     */
    public function validate($data, $schema): bool
    {
        $result = $this->validator->validate($data, self::API_URL . DIRECTORY_SEPARATOR . $schema);
        if ($result->isValid() === false) {
            $errorFormat = (new ErrorFormatter())->format($result->error());
            throw new ContractsValidatorException(json_encode($errorFormat));
        }
        return true;
    }
}
