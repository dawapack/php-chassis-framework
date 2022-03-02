<?php

namespace Chassis\Tests;

use Chassis\Application;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected Application $app;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->bootApplication();
        parent::__construct($name, $data, $dataName);
    }

    private function bootApplication()
    {
        $this->app = new Application(dirname(__DIR__));
    }
}