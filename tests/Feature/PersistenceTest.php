<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\Agent;
use Agentic\Models\Skill;
use Agentic\Models\Tool;
use Agentic\Models\ToolVersion;
use Agentic\Tests\TestCase;
use Agentic\Tool\ToolVersionPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class PersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_skill_tool_graph_is_persisted(): void
    {
        $agent = Agent::create(['name' => 'Support', 'slug' => 'support']);
        $skill = Skill::create(['name' => 'Orders', 'slug' => 'orders']);
        $tool = Tool::create(['name' => 'Get Order', 'slug' => 'get-order', 'type' => 'http', 'driver' => 'http']);

        $agent->skills()->attach($skill, ['position' => 0]);
        $skill->tools()->attach($tool, ['position' => 0]);

        $this->assertTrue($agent->skills->contains($skill));
        $this->assertTrue($skill->tools->contains($tool));
    }

    public function test_publishing_creates_incremental_versions(): void
    {
        $tool = Tool::create(['name' => 'Get Order', 'slug' => 'get-order', 'type' => 'http', 'driver' => 'http']);
        $publisher = app(ToolVersionPublisher::class);

        $first = $publisher->publish($tool, ['method' => 'GET', 'url' => '/orders/{id}']);
        $second = $publisher->publish($tool->fresh(), ['method' => 'GET', 'url' => '/orders/{id}/items']);

        $this->assertInstanceOf(ToolVersion::class, $first);
        $this->assertSame(1, $first->version);
        $this->assertSame(2, $second->version);
        $this->assertSame(2, $tool->fresh()->latestPublishedVersion->version);
        $this->assertSame('published', $tool->fresh()->status->value);
    }
}
