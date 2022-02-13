<?php

declare(strict_types=1);

namespace Chassis\Framework\Logger;

use Chassis\Framework\Logger\DataTransferObject\ContextError;
use Chassis\Framework\Logger\DataTransferObject\Record;
use Throwable;

use function Chassis\Helpers\env;

class LoggerProcessor
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    /**
     * @var Record $record
     */
    private Record $record;
    private array $except;

    /**
     * LoggerProcessor constructor.
     *
     * @param Record|null $record
     */
    public function __construct(?Record $record = null)
    {
        $this->record = $record ?? $this->createDefaultRecord();
    }

    /**
     * @param array $loggerRecord
     *
     * @return array
     */
    public function __invoke(array $loggerRecord): array
    {
        $records = $this->decorateRecord($loggerRecord)->toArray();
        $records["context"] = array_diff_key(
            $records["context"],
            array_flip($this->except)
        );
        return $records;
    }

    /**
     * @return Record
     */
    private function createDefaultRecord(): Record
    {
        $defaultRecord = [
            "timestamp" => "",
            "level" => "",
            "message" => "",
        ];

        $defaultRecord['origin'] = env("ORIGIN", "unknown");
        $defaultRecord['region'] = env("REGION", "unknown");
        $defaultRecord["application"] = [
            "name" => env("APP_SYSNAME", null),
            "environment" => env("APP_ENV", null),
            "type" => RUNNER_TYPE
        ];
        $defaultRecord['component'] = env("APP_LOGCOMPONENT", "application_unhandled_exception");
        $defaultRecord["extra"] = null;
        $defaultRecord["context"] = ['error' => []];

        return new Record($defaultRecord);
    }

    /**
     * @param array $loggerRecord
     *
     * @return Record
     */
    private function decorateRecord(array $loggerRecord): Record
    {
        $this->except = [];

        $this->record->timestamp = $loggerRecord["datetime"]->format(self::DATETIME_FORMAT);
        $this->record->level = $loggerRecord["level_name"];
        $this->record->message = $loggerRecord["message"];

        if (isset($loggerRecord["context"]["component"])) {
            $this->record->component = $loggerRecord["context"]["component"];
        }

        $extra = array_diff_key(
            $loggerRecord["context"],
            array_flip(["component", "error"])
        );
        $this->record->extra = !empty($extra) ? json_encode($extra) : "";

        if (
            isset($loggerRecord["context"]["error"])
            && $loggerRecord["context"]["error"] instanceof Throwable
        ) {
            $this->record->context->error = (new ContextError())
                ->fillFromThrowable($loggerRecord["context"]["error"]);
        } else {
            $this->except[] = "error";
        }

        return $this->record;
    }
}
