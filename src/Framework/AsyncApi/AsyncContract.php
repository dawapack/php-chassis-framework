<?php

declare(strict_types=1);

namespace Chassis\Framework\AsyncApi;

use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractValidatorException;

class AsyncContract implements AsyncContractInterface
{
    private TransformersInterface $transformer;
    private ContractParser $parser;
    private ContractValidator $validator;

    private array $configuration;
    private string $protocol;

    /**
     * @param ContractParser $parser
     * @param ContractValidator $validator
     */
    public function __construct(
        ContractParser $parser,
        ContractValidator $validator
    ) {
        $this->parser = $parser;
        $this->validator = $validator;
    }

    /**
     * @param array $configuration
     *
     * @return $this
     *
     * @throws AsyncContractParserException
     * @throws AsyncContractValidatorException
     */
    public function setConfiguration(array $configuration): AsyncContract
    {
        $this->configuration = $configuration;
        $this->protocol = $this->configuration["connections"][$this->configuration["connection"]]["protocol"];
        $this->validate();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function pushTransformer(TransformersInterface $transformer): AsyncContract
    {
        $this->transformer = $transformer->setConnection(
            $this->configuration["connections"][$this->configuration["connection"]]
        );
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getChannelBindings(string $channel): object
    {
        $channelType = $this->getChannelType($channel);
        return $this->getChannelDefinitions($channel)->bindings->{$this->protocol}->{$channelType};
    }

    /**
     * @inheritDoc
     */
    public function getChannelType(string $channel): string
    {
        $channelBindings = $this->getChannelDefinitions($channel)->bindings->{$this->protocol};
        return $this->getChannelOperation($channelBindings) === ContractValidator::OPERATION_PUBLISH
            ? "exchange"
            : "queue";
    }

    /**
     * @inheritDoc
     */
    public function getOperationBindings(string $channel): object
    {
        $operation = $this->getChannelOperation(
            $this->getChannelDefinitions($channel)->bindings->{$this->protocol}
        );
        return $this->getChannelDefinitions($channel)->{$operation}->bindings->{$this->protocol};
    }

    /**
     * @inheritDoc
     */
    public function getMessageBindings(string $channel): object
    {
        $operation = $this->getChannelOperation(
            $this->getChannelDefinitions($channel)->bindings->{$this->protocol}
        );
        return $this->getChannelDefinitions($channel)->{$operation}->message->bindings->{$this->protocol};
    }

    /**
     * @inheritDoc
     */
    public function getTransformer(): ?TransformersInterface
    {
        return $this->transformer ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getChannels(): array
    {
        $channels = $this->parser->getContract()->channels ?? [];
        return !is_array($channels) ? (array)$channels : $channels;
    }

    /**
     * @inheritDoc
     */
    public function transform(string $channel): TransformersInterface
    {
        return $this->transformer
            ->setBindings(
                $this->getChannelBindings($channel),
                $this->getOperationBindings($channel),
                $this->getMessageBindings($channel)
            );
    }

    /**
     * @param object $channelBindings
     *
     * @return string
     */
    protected function getChannelOperation(object $channelBindings): string
    {
        return $channelBindings->is === ContractValidator::IS_ROUTING_KEY
            ? ContractValidator::OPERATION_PUBLISH
            : ContractValidator::OPERATION_SUBSCRIBE;
    }

    /**
     * @return void
     *
     * @throws AsyncContractParserException
     * @throws AsyncContractValidatorException
     */
    protected function validate(): void
    {
        $this->parser
            ->setConfiguration($this->configuration)
            ->parse();
        $this->validator
            ->validate(
                $this->parser->getContract(),
                $this->parser->getValidatorsPath(),
                $this->protocol
            );
    }

    /**
     * @param string|null $channelName
     *
     * @return object
     */
    protected function getChannelDefinitions(string $channelName = null): object
    {
        $contractChannels = $this->parser->getContract()->channels;
        return is_null($channelName) ? $contractChannels : $contractChannels->{$channelName};
    }
}
