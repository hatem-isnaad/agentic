<?php

namespace Agentic\Tests\Unit;

use Agentic\Http\Support\AuthRequirement;
use Agentic\Tests\TestCase;

final class AuthRequirementTest extends TestCase
{
    public function test_testing_stays_open_when_unset(): void
    {
        $this->assertFalse(AuthRequirement::enabled(null));
        $this->assertFalse(AuthRequirement::enabled(''));
    }

    public function test_production_requires_auth_when_unset(): void
    {
        $this->app['env'] = 'production';

        $this->assertTrue(AuthRequirement::enabled(null));
    }

    public function test_explicit_false_stays_open(): void
    {
        $this->app['env'] = 'production';

        $this->assertFalse(AuthRequirement::enabled(false));
        $this->assertFalse(AuthRequirement::enabled('false'));
    }

    public function test_explicit_true_always_requires(): void
    {
        $this->assertTrue(AuthRequirement::enabled(true));
        $this->assertTrue(AuthRequirement::enabled('true'));
    }

    public function test_package_config_defaults_close_admin_and_runtime_apis(): void
    {
        $config = require dirname(__DIR__, 2).'/config/agentic.php';

        $this->assertTrue($config['auth']['protect']['admin_api']);
        $this->assertTrue($config['auth']['protect']['runtime_api']);
        $this->assertFalse($config['knowledge']['log_queries']);
        $this->assertFalse($config['knowledge']['log_embeddings']);
    }
}
