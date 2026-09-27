<?php

namespace Agentic\Tests;

use Agentic\AgenticServiceProvider;
use Laravel\Ai\AiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
            AgenticServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('agentic.execution.driver', 'memory');
        $app['config']->set('agentic.conversation.driver', 'memory');
        $app['config']->set('agentic.memory.driver', 'memory');
        $app['config']->set('agentic.http.allow_unresolved_hosts', true);
    }
}
