<?php

namespace Agentic\Tests\Feature;

use Agentic\Enums\Status;
use Agentic\Models\Tool;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ResourceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
    }

    public function test_skill_crud_via_api(): void
    {
        $tool = Tool::create([
            'name' => 'Search',
            'slug' => 'orders.search',
            'type' => 'http',
            'driver' => 'http',
            'status' => Status::Published,
        ]);

        $create = $this->postJson('/api/agentic/skills', [
            'name' => 'Orders',
            'slug' => 'orders',
            'description' => 'Order skill',
            'status' => 'published',
            'tools' => ['orders.search'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.slug', 'orders')
            ->assertJsonPath('data.tools.0', 'orders.search');

        $this->getJson('/api/agentic/skills/orders')
            ->assertOk()
            ->assertJsonPath('data.description', 'Order skill');

        $this->putJson('/api/agentic/skills/orders', [
            'name' => 'Orders v2',
            'slug' => 'orders',
            'description' => 'Updated',
            'status' => 'published',
            'tools' => ['orders.search'],
        ])->assertOk()->assertJsonPath('data.description', 'Updated');

        $this->deleteJson('/api/agentic/skills/orders')->assertNoContent();

        unset($tool);
    }

    public function test_knowledge_source_crud_and_index_endpoint(): void
    {
        $this->postJson('/api/agentic/knowledge-sources', [
            'name' => 'FAQ',
            'slug' => 'faq',
            'driver' => 'array',
            'status' => 'published',
            'config' => [
                'documents' => ['Returns within 30 days.'],
            ],
        ])->assertCreated();

        $this->postJson('/api/agentic/knowledge-sources/faq/index')
            ->assertOk()
            ->assertJsonPath('message', 'Knowledge source indexed.');

        $this->getJson('/api/agentic/knowledge-sources/faq')
            ->assertOk()
            ->assertJsonPath('data.slug', 'faq');
    }

    public function test_tool_create_and_publish_via_api(): void
    {
        $this->postJson('/api/agentic/tools', [
            'name' => 'Get Order',
            'slug' => 'get-order',
            'driver' => 'http',
            'status' => 'published',
            'publish' => true,
            'definition' => [
                'method' => 'GET',
                'url' => 'https://example.test/orders/{id}',
                'input_schema' => ['id' => ['type' => 'string', 'required' => true]],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'get-order')
            ->assertJsonPath('data.driver', 'http');
    }
}
