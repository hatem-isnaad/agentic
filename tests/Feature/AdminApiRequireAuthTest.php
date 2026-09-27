<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Models\Agent;
use Agentic\Tests\Models\User;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\SanctumServiceProvider;

final class AdminApiRequireAuthTest extends TestCase
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
        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
        $app['config']->set('agentic.auth.enabled', true);
        $app['config']->set('agentic.auth.protect.admin_api', true);
    }

    public function test_admin_api_returns_unauthorized_without_token(): void
    {
        $prefix = trim((string) config('agentic.admin.api.prefix'), '/');

        $this->getJson('/'.$prefix.'/dashboard')->assertUnauthorized();
    }

    public function test_admin_api_accepts_sanctum_token_when_auth_required(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
        ]);

        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);

        $token = $user->createToken('admin')->plainTextToken;
        $prefix = trim((string) config('agentic.admin.api.prefix'), '/');

        $this->withToken($token)
            ->getJson('/'.$prefix.'/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.agents', 1);
    }
}
