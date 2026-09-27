<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

final class AdminGateTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
    }

    public function test_admin_api_denied_when_gate_fails(): void
    {
        config(['agentic.admin.authorization.gate' => 'viewAgenticDeny']);
        Gate::define('viewAgenticDeny', fn () => false);

        $prefix = trim((string) config('agentic.admin.api.prefix'), '/');

        $this->getJson('/'.$prefix.'/dashboard')->assertForbidden();
    }

    public function test_admin_api_open_when_gate_not_configured(): void
    {
        config(['agentic.admin.authorization.gate' => null]);

        $prefix = trim((string) config('agentic.admin.api.prefix'), '/');

        $this->getJson('/'.$prefix.'/dashboard')->assertOk();
    }
}
