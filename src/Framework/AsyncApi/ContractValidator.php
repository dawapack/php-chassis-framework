<?php

declare(strict_types=1);

namespace Chassis\Framework\AsyncApi;

use Chassis\Framework\AsyncApi\Exceptions\AsyncContractValidatorException;
use Opis\JsonSchema\Validator;

class ContractValidator
{
    private const SCHEMA_IS_MANDATORY_MESSAGE = 'schema is mandatory';
    private const LOADING_SCHEMAS_FAIL_MESSAGE = 'loading schemas fail';
    private const VALIDATION_FAIL_MESSAGE = 'channel \'%s\' validation fail';
    private const CHANNEL = 'channel.json';
    private const OPERATION = 'operation.json';
    private const MESSAGE = 'message.json';
    public const IS_ROUTING_KEY = 'routingKey';
    public const OPERATION_PUBLISH = 'publish';
    public const OPERATION_SUBSCRIBE = 'subscribe';

    private Validator $validator;
    private array $schemas;
    private bool $isValid = false;

    /**
     * @param Validator $validator
     */
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param object $contract
     * @param string $validatorsPath
     * @param string $protocol
     *
     * @return void
     *
     * @throws AsyncContractValidatorException
     */
    public function validate(object $contract, string $validatorsPath, string $protocol): void
    {
        $this->loadSchemas($validatorsPath);
        if (empty($this->schemas)) {
            throw new AsyncContractValidatorException(self::LOADING_SCHEMAS_FAIL_MESSAGE);
        }
        $channels = (array)$contract->channels;
        // validate each channel
        foreach ($channels as $name => $channel) {
            // validate channel bindings
            $this->validateBindings(
                $name,
                $channel->bindings->{$protocol},
                $this->schemas[self::CHANNEL]
            );
            // validate operation bindings & message bindings
            $operation = $channel->bindings->{$protocol}->is === self::IS_ROUTING_KEY
                ? self::OPERATION_PUBLISH
                : self::OPERATION_SUBSCRIBE;
            $this->validateBindings(
                $name,
                $channel->{$operation}->bindings->{$protocol},
                $this->schemas[self::OPERATION]
            );
            $this->validateBindings(
                $name,
                $channel->{$operation}->message->bindings->{$protocol},
                $this->schemas[self::MESSAGE]
            );
        }

        $this->isValid = true;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @param string $channelName
     * @param object $bindings
     * @param object|null $schema
     *
     * @return void
     *
     * @throws AsyncContractValidatorException
     */
    protected function validateBindings(string $channelName, object $bindings, ?object $schema): void
    {
        if (is_null($schema)) {
            throw new AsyncContractValidatorException(self::SCHEMA_IS_MANDATORY_MESSAGE);
        }
        $result = $this->validator->validate($bindings, $schema);
        if ($result->hasError()) {
            throw new AsyncContractValidatorException(
                sprintf(self::VALIDATION_FAIL_MESSAGE, $channelName)
            );
        }
    }

    /**
     * @return string[]
     */
    protected function getAllSchemaNames(): array
    {
        return [
            self::CHANNEL,
            self::OPERATION,
            self::MESSAGE,
        ];
    }

    /**
     * @param string $validatorsPath
     *
     * @return void
     */
    protected function loadSchemas(string $validatorsPath): void
    {
        if (!empty($this->schemas) || !is_dir($validatorsPath)) {
            return;
        }
        $this->schemas = [];
        foreach ($this->getAllSchemaNames() as $schemaName) {
            $filePath = $validatorsPath . DIRECTORY_SEPARATOR . $schemaName;
            if (!file_exists($filePath)) {
                continue;
            }
            $this->schemas[$schemaName] = json_decode(file_get_contents($filePath));
        }
    }
}
