<?php

namespace Agentic\Tests\Unit;

use Agentic\Context\ContextBuilder;
use Agentic\Context\RuntimeContext;
use Agentic\Agent\AgentDefinition;
use Agentic\Memory\MemoryManager;
use Agentic\Memory\MemoryScope;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class MemoryManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.memory.driver', 'eloquent');
        $app['config']->set('agentic.memory.enabled', true);
    }

    public function test_user_memory_is_recalled_for_runtime_context(): void
    {
        app(MemoryManager::class)->remember(
            MemoryScope::User,
            '42',
            'name',
            'Hatem',
        );

        $records = app(MemoryManager::class)->recallForRuntime(
            new RuntimeContext(['user_id' => '42']),
            'support',
        );

        $this->assertCount(1, $records);
        $this->assertSame('name', $records[0]->key);
        $this->assertSame('Hatem', $records[0]->content);
    }

    public function test_context_builder_injects_memory_into_payload(): void
    {
        app(MemoryManager::class)->remember(
            MemoryScope::User,
            '99',
            'locale',
            'ar',
        );

        $agent = new AgentDefinition(
            name: 'Support',
            instructions: 'Help users.',
            slug: 'support',
        );

        $built = app(ContextBuilder::class)->build(
            $agent,
            'hello',
            new RuntimeContext(['user_id' => '99']),
        );

        $this->assertArrayHasKey('memory', $built);
        $this->assertSame('locale', $built['memory'][0]['key']);
        $this->assertSame('ar', $built['memory'][0]['content']);
    }
}
