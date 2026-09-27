<?php

namespace Agentic\Tests\Unit;

use Agentic\Mcp\McpServerService;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\Drivers\Mcp\ArrayMcpClientGateway;
use Agentic\Tests\TestCase;

final class McpServerServiceTest extends TestCase
{
    public function test_catalog_lists_resources_and_prompts(): void
    {
        $gateway = new ArrayMcpClientGateway();
        $gateway->registerCatalog('docs', [
            'tools' => [],
            'resources' => [
                ['uri' => 'file:///policy.md', 'name' => 'policy', 'mime_type' => 'text/markdown', 'text' => '# Policy'],
            ],
            'prompts' => [
                [
                    'name' => 'summarize',
                    'description' => 'Summarize text',
                    'handler' => fn (array $args) => [
                        'description' => 'Summary',
                        'messages' => [['role' => 'user', 'content' => (string) ($args['text'] ?? '')]],
                    ],
                ],
            ],
        ]);

        $this->app->instance(McpClientGateway::class, $gateway);
        config(['mcp.servers' => ['docs' => []]]);

        $service = app(McpServerService::class);

        $this->assertCount(1, $service->resources('docs'));
        $this->assertSame('# Policy', $service->readResource('docs', 'file:///policy.md')['text']);
        $this->assertSame('summarize', $service->prompts('docs')[0]['name']);
        $this->assertSame('hello', $service->prompt('docs', 'summarize', ['text' => 'hello'])['messages'][0]['content']);
    }
}
