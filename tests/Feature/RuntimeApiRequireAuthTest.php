<?php

namespace Agentic\Tests\Feature;

use Agentic\Agent\AgentDefinition;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Tests\Models\User;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\AnonymousAgent;
use Laravel\Sanctum\SanctumServiceProvider;

final class RuntimeApiRequireAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            SanctumServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../../vendor/laravel/sanctum/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('agentic.api.enabled', true);
        $app['config']->set('agentic.auth.enabled', true);
        $app['config']->set('agentic.auth.protect.runtime_api', true);
        $app['config']->set('agentic.knowledge.driver', 'memory');
    }

    public function test_runtime_api_returns_unauthorized_without_token(): void
    {
        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $this->postJson('/'.$prefix.'/route', [
            'agent' => 'billing',
            'message' => 'Hello',
        ])->assertUnauthorized();
    }

    public function test_runtime_api_accepts_sanctum_token_when_auth_required(): void
    {
        AnonymousAgent::fake(['Authenticated response']);

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

        $user = User::query()->create([
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => bcrypt('secret'),
        ]);

        $token = $user->createToken('runtime')->plainTextToken;
        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $this->withToken($token)
            ->postJson('/'.$prefix.'/agents/support/execute', ['message' => 'Hi'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Authenticated response');
    }
}
