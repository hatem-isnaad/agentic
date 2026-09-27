<?php

namespace Agentic\Tests\Feature;

use Agentic\Agent\AgentResolver;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Enums\Status;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Models\Agent;
use Agentic\Models\Skill;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AgentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_resolves_agent_definition_without_runtime_eloquent_coupling(): void
    {
        $skill = Skill::create([
            'name' => 'Orders',
            'slug' => 'orders',
            'description' => 'Order ops',
            'status' => Status::Published,
        ]);

        $agent = Agent::create([
            'name' => 'Support Agent',
            'slug' => 'support',
            'instructions' => 'Help customers.',
            'status' => Status::Published,
            'model_config' => [
                'provider' => 'openai',
                'model' => 'gpt-4.1-mini',
                'temperature' => 0.2,
            ],
        ]);

        $agent->skills()->attach($skill, ['position' => 0]);

        $definition = app(AgentRepository::class)->findBySlug('support');

        $this->assertNotNull($definition);
        $this->assertSame('Support Agent', $definition->name);
        $this->assertSame('support', $definition->slug);
        $this->assertSame(['orders'], $definition->skills);
        $this->assertSame('openai', $definition->provider);
        $this->assertSame('gpt-4.1-mini', $definition->model);

        $resolved = app(AgentResolver::class)->resolve('support');
        $this->assertSame('support', $resolved->slug);

        $this->expectException(AgentNotFoundException::class);
        app(AgentResolver::class)->resolve('missing');
    }
}
