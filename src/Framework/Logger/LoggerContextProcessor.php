<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger;

class LoggerContextProcessor
{
    private LoggerApplicationContextInterface $applicationContext;
    private array $except;

    /**
     * @param LoggerApplicationContextInterface $applicationContext
     */
    public function __construct(LoggerApplicationContextInterface $applicationContext)
    {
        $this->applicationContext = $applicationContext;
    }

    /**
     * @param array $loggerRecord
     *
     * @return array
     */
    public function __invoke(array $loggerRecord): array
    {
        $loggerRecord["context"] = array_diff_key(
            array_merge(
                $loggerRecord["context"],
                $this->decorateRecord()
            ),
            array_flip($this->except)
        );
        return $loggerRecord;
    }

    /**
     * @return array
     */
    private function decorateRecord(): array
    {
        $this->except = [];
        $context = [];

        $requestContext = $this->applicationContext->getBrokerContext();
        if (is_null($requestContext)) {
            $this->except[] = "broker";
        } else {
            $context["broker"] = $requestContext->toArray();
        }

        return $context;
    }
}
