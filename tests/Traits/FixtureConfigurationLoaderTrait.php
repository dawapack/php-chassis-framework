<?php

declare(strict_types=1);

namespace ChassisTests\Traits;

use Exception;

trait FixtureConfigurationLoaderTrait
{
    /**
     * @param $file
     *
     * @return array
     *
     * @throws Exception
     */
    public function loadFixtureConfiguration($file): array
    {
        if (!is_file(__DIR__ . "/../Fixtures/Configurations/" . $file . ".php")) {
            throw new Exception("fixture file '$file' not found");
        }
        return require __DIR__ . "/../Fixtures/Configurations/" . $file . ".php";
    }
}
