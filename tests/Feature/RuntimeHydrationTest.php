<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\Agent;
use Agentic\Models\Skill;
use Agentic\Models\Tool;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Tests\TestCase;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class RuntimeHydrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolving_an_agent_hydrates_published_skill_tools(): void
    {
        $agent = Agent::create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => 'published',
        ]);

        $skill = Skill::create([
            'name' => 'Orders',
            'slug' => 'orders',
            'status' => 'published',
        ]);

        $tool = Tool::create([
            'name' => 'Get Order',
            'slug' => 'get-order',
            'type' => 'http',
            'driver' => 'http',
            'status' => 'published',
        ]);

        $agent->skills()->attach($skill);
        $skill->tools()->attach($tool);

        app(ToolVersionPublisher::class)->publish($tool, [
            'input_schema' => [
                'type' => 'object',
                'properties' => ['id' => ['type' => 'string']],
                'required' => ['id'],
            ],
            'method' => 'GET',
            'url' => 'https://example.test/orders/{id}',
        ]);

        $definition = app(\Agentic\Agent\AgentResolver::class)->resolve('support');

        $this->assertContains('orders', $definition->skills);
        $this->assertTrue(app(ToolRegistry::class)->has('get-order'));
        $this->assertSame(1, app(ToolRegistry::class)->get('get-order')->definition()->version);
    }

    public function test_direct_agent_tool_is_hydrated_too(): void
    {
        $agent = Agent::create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => 'published',
            'config' => ['tools' => ['get-order']],
        ]);

        Tool::create([
            'name' => 'Get Order',
            'slug' => 'get-order',
            'type' => 'http',
            'driver' => 'http',
            'status' => 'published',
        ]);

        app(ToolVersionPublisher::class)->publish(
            Tool::query()->where('slug', 'get-order')->firstOrFail(),
            ['method' => 'GET', 'url' => 'https://example.test/orders'],
        );

        app(\Agentic\Agent\AgentResolver::class)->resolve('support');

        $this->assertTrue(app(ToolRegistry::class)->has('get-order'));
    }
}
