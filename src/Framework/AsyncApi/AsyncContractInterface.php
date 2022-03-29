<?php

declare(strict_types=1);

namespace Chassis\Framework\AsyncApi;

use Chassis\Framework\AsyncApi\Exceptions\AsyncContractParserException;
use Chassis\Framework\AsyncApi\Exceptions\AsyncContractValidatorException;

interface AsyncContractInterface
{
    /**
     * @param array $configuration
     *
     * @return $this
     *
     * @throws AsyncContractParserException
     * @throws AsyncContractValidatorException
     */
    public function setConfiguration(array $configuration): self;

    /**
     * @return TransformersInterface|null
     */
    public function getTransformer(): ?TransformersInterface;

    /**
     * @return array
     */
    public function getChannels(): array;

    /**
     * @param string $channel
     *
     * @return object
     */
    public function getChannelBindings(string $channel): object;

    /**
     * @param string $channel
     *
     * @return string
     */
    public function getChannelType(string $channel): string;

    /**
     * @param string $channel
     *
     * @return object
     */
    public function getOperationBindings(string $channel): object;

    /**
     * @param string $channel
     *
     * @return object
     */
    public function getMessageBindings(string $channel): object;

    /**
     * @param TransformersInterface $transformer
     *
     * @return AsyncContract
     */
    public function pushTransformer(TransformersInterface $transformer): AsyncContract;

    /**
     * @param string $channel
     *
     * @return TransformersInterface
     */
    public function transform(string $channel): TransformersInterface;
}
