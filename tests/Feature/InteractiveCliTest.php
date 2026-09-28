<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\Agent;
use Agentic\Models\ChannelAccount;
use Agentic\Models\Evaluation;
use Agentic\Models\KnowledgeSource;
use Agentic\Models\Skill;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class InteractiveCliTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_create_without_prompts_when_flags_set(): void
    {
        $this->artisan('agentic:agent', [
            'action' => 'create',
            '--name' => 'Support',
            '--slug' => 'support',
            '--instructions' => 'Help visitors.',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertSame(1, Agent::query()->where('slug', 'support')->count());
    }

    public function test_skill_create_without_prompts_when_flags_set(): void
    {
        $this->artisan('agentic:skill', [
            'action' => 'create',
            '--name' => 'Orders',
            '--slug' => 'orders',
            '--tools' => 'limenos-orders-get',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertSame(1, Skill::query()->where('slug', 'orders')->count());
    }

    public function test_knowledge_create_without_prompts_when_flags_set(): void
    {
        $this->artisan('agentic:knowledge', [
            'action' => 'create',
            '--name' => 'Policies',
            '--slug' => 'policies',
            '--driver' => 'array',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertSame(1, KnowledgeSource::query()->where('slug', 'policies')->count());
    }

    public function test_evaluation_create_without_prompts_when_flags_set(): void
    {
        $this->artisan('agentic:evaluation', [
            'action' => 'create',
            '--score' => '4',
            '--agent' => 'support',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertSame(1, Evaluation::query()->where('score', 4)->count());
    }

    public function test_channel_account_create_without_prompts_when_flags_set(): void
    {
        $this->artisan('agentic:channel-account', [
            'action' => 'create',
            '--name' => 'Sales WhatsApp',
            '--slug' => 'sales-wa',
            '--channel' => 'whatsapp',
            '--driver' => 'meta_cloud',
            '--agent' => 'support',
            '--external-id' => '111',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertSame(1, ChannelAccount::query()->where('slug', 'sales-wa')->count());
    }

    public function test_manage_lists_connections_without_prompts(): void
    {
        $this->artisan('agentic:manage', [
            'action' => 'list',
            'resource' => 'connection',
            '--no-interaction' => true,
        ])->assertSuccessful();
    }

    public function test_agent_create_asks_then_cancels_when_not_confirmed(): void
    {
        $this->artisan('agentic:agent', ['action' => 'create'])
            ->expectsQuestion('Agent name', 'Support')
            ->expectsQuestion('Slug', 'support')
            ->expectsQuestion('Instructions', 'Help.')
            ->expectsQuestion('AI provider (empty = package default)', '')
            ->expectsQuestion('Model (empty = package default)', '')
            ->expectsQuestion('Skill slugs (comma, optional)', '')
            ->expectsQuestion('Tool slugs (comma, optional)', '')
            ->expectsQuestion('Knowledge slugs (comma, optional)', '')
            ->expectsConfirmation('Save this agent?', false)
            ->assertSuccessful();

        $this->assertSame(0, Agent::query()->count());
    }
}
