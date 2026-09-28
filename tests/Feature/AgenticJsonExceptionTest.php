<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

final class AgenticJsonExceptionTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
        $app['config']->set('agentic.admin.authorization.gate', 'viewAgenticDeny');
        Gate::define('viewAgenticDeny', fn () => false);
    }

    public function test_admin_api_auth_errors_are_clean_json_without_trace(): void
    {
        $prefix = trim((string) config('agentic.admin.api.prefix'), '/');

        $response = $this->getJson('/'.$prefix.'/dashboard');

        $response
            ->assertForbidden()
            ->assertJsonStructure(['message'])
            ->assertJsonMissing(['exception', 'trace']);
    }
}
