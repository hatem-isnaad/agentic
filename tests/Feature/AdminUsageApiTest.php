<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\Execution;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

final class AdminUsageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
        $app['config']->set('agentic.execution.driver', 'eloquent');
        $app['config']->set('agentic.usage.models.claude-sonnet-5', ['input' => 3.0, 'output' => 15.0]);
    }

    public function test_usage_endpoint_sums_execution_tokens(): void
    {
        Execution::query()->create([
            'uuid' => (string) Str::uuid(),
            'agent' => 'support',
            'status' => 'completed',
            'output' => ['usage' => ['input_tokens' => 1000, 'output_tokens' => 100]],
            'metadata' => ['model' => 'claude-sonnet-5'],
        ]);

        $this->getJson('/api/agentic/admin/usage')
            ->assertOk()
            ->assertJsonPath('data.tokens_in', 1000)
            ->assertJsonPath('data.tokens_out', 100)
            ->assertJsonPath('data.by_agent.0.agent', 'support')
            ->assertJsonPath('data.by_day.0.executions', 1)
            ->assertJsonPath('data.messages_per_20_usd', fn ($n) => is_int($n) && $n > 0);
    }
}
