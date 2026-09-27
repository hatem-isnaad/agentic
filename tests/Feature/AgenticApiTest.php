<?php

namespace Agentic\Tests\Feature;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Routing\AgentRouter;
use Agentic\Routing\Strategies\SlugRoutingStrategy;
use Agentic\Tests\TestCase;
use Laravel\Ai\AnonymousAgent;

final class AgenticApiTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
        $app['config']->set('agentic.knowledge.driver', 'memory');
    }

    public function test_execute_endpoint_runs_agent_through_runtime(): void
    {
        AnonymousAgent::fake(['API response']);

        $this->app->bind(AgentRepository::class, fn () => new class implements AgentRepository {
            public function findById(int|string $id): ?AgentDefinition
            {
                return null;
            }

            public function findBySlug(string $slug): ?AgentDefinition
            {
                if ($slug !== 'support') {
                    return null;
                }

                return new AgentDefinition(
                    name: 'Support',
                    instructions: 'Help users.',
                    slug: 'support',
                    model: 'gpt-4.1-mini',
                    provider: 'openai',
                );
            }

            public function allPublished(): array
            {
                return [];
            }

            public function all(): array
            {
                return [];
            }

            public function save(array $attributes): AgentDefinition
            {
                unset($attributes);

                throw new \BadMethodCallException();
            }

            public function delete(string $slug): bool
            {
                unset($slug);

                return false;
            }
        });

        $response = $this->postJson('/api/agentic/agents/support/execute', [
            'message' => 'Hello',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.text', 'API response');
    }

    public function test_route_endpoint_returns_routing_decision(): void
    {
        $this->app->singleton(AgentRouter::class, function () {
            return (new AgentRouter('fallback'))
                ->use(new SlugRoutingStrategy());
        });

        $response = $this->postJson('/api/agentic/route', [
            'agent' => 'billing',
            'message' => 'Invoice question',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.agent', 'billing')
            ->assertJsonPath('data.strategy', 'slug');
    }
}
