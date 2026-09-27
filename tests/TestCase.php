<?php

namespace Agentic\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            \Agentic\AgenticServiceProvider::class,
        ];
    }
}
