<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\Drivers\Mcp\ArrayMcpClientGateway;

final class McpCatalogApiTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.api.enabled', true);
    }

    public function test_mcp_resource_catalog_endpoints_return_data(): void
    {
        $gateway = new ArrayMcpClientGateway();
        $gateway->registerCatalog('docs', [
            'resources' => [
                ['uri' => 'file:///a.md', 'name' => 'A', 'text' => 'Alpha'],
            ],
            'prompts' => [
                ['name' => 'summarize', 'description' => 'Sum'],
            ],
        ]);

        $this->app->instance(McpClientGateway::class, $gateway);
        config(['mcp.servers' => ['docs' => []]]);

        $prefix = trim((string) config('agentic.api.prefix'), '/');

        $this->getJson('/'.$prefix.'/mcp/servers/docs/resources')
            ->assertOk()
            ->assertJsonPath('data.0.uri', 'file:///a.md');

        $this->postJson('/'.$prefix.'/mcp/servers/docs/resources/read', [
            'uri' => 'file:///a.md',
        ])
            ->assertOk()
            ->assertJsonPath('data.text', 'Alpha');

        $this->getJson('/'.$prefix.'/mcp/servers/docs/prompts')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'summarize');
    }
}
