<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;

final class SecurityDefaultsTest extends TestCase
{
    public function test_permissions_default_to_deny(): void
    {
        $this->assertSame('deny', config('agentic.permissions.default'));
    }

    public function test_http_private_hosts_are_blocked_by_default(): void
    {
        $this->assertFalse(config('agentic.http.allow_private_hosts'));
        $this->assertFalse(config('agentic.http.allow_redirects'));
    }

    public function test_runtime_api_rate_limit_is_enabled_by_default(): void
    {
        $defaults = require dirname(__DIR__, 2).'/config/agentic.php';

        $this->assertTrue($defaults['api']['rate_limit']['enabled']);
        $this->assertGreaterThan(0, (int) $defaults['api']['rate_limit']['per_minute']);
    }
}
