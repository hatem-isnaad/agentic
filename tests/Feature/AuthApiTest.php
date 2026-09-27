<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\Models\User;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\SanctumServiceProvider;

final class AuthApiTest extends TestCase
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
        $app['config']->set('agentic.auth.enabled', true);
    }

    public function test_me_requires_authentication(): void
    {
        $prefix = trim((string) config('agentic.auth.prefix'), '/');

        $this->getJson('/'.$prefix.'/me')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::query()->create([
            'name' => 'Agentic Tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('secret'),
        ]);

        $token = $user->createToken('test')->plainTextToken;
        $prefix = trim((string) config('agentic.auth.prefix'), '/');

        $this->withToken($token)
            ->getJson('/'.$prefix.'/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'tester@example.com');
    }

    public function test_token_endpoint_issues_plain_text_token(): void
    {
        $user = User::query()->create([
            'name' => 'Agentic Tester',
            'email' => 'token@example.com',
            'password' => bcrypt('secret'),
        ]);

        Sanctum::actingAs($user);
        $prefix = trim((string) config('agentic.auth.prefix'), '/');

        $response = $this->postJson('/'.$prefix.'/token', ['token_name' => 'ci']);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }
}
