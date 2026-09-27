<?php

namespace Agentic\Tests\Feature;

use Agentic\Memory\MemoryScope;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class MemoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
        $app['config']->set('agentic.memory.driver', 'eloquent');
    }

    public function test_memory_can_be_stored_and_listed_via_api(): void
    {
        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $this->postJson('/'.$prefix.'/memories', [
            'scope' => MemoryScope::User,
            'scope_key' => '7',
            'key' => 'plan',
            'content' => 'Pro tier',
            'importance' => 8,
        ])->assertCreated()
            ->assertJsonPath('data.key', 'plan');

        $this->getJson('/'.$prefix.'/memories?scope=user&scope_key=7')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Pro tier');
    }

    public function test_memory_can_be_deleted_via_api(): void
    {
        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $response = $this->postJson('/'.$prefix.'/memories', [
            'scope' => MemoryScope::Tenant,
            'scope_key' => 'acme',
            'key' => 'note',
            'content' => 'temporary',
        ])->assertCreated();

        $id = $response->json('data.id');

        $this->deleteJson('/'.$prefix.'/memories/'.$id)
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->getJson('/'.$prefix.'/memories?scope=tenant&scope_key=acme')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
