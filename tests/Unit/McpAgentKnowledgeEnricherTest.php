<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Mcp\McpAgentKnowledgeEnricher;
use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\McpClientGateway;
use Agentic\Tool\Drivers\Mcp\ArrayMcpClientGateway;

final class McpAgentKnowledgeEnricherTest extends TestCase
{
    public function test_mcp_resources_are_injected_from_agent_runtime_config(): void
    {
        $gateway = new ArrayMcpClientGateway;
        $gateway->registerCatalog('docs', [
            'resources' => [
                [
                    'uri' => 'file:///policy.md',
                    'text' => 'Return policy: 30 days.',
                ],
            ],
        ]);

        $this->app->instance(McpClientGateway::class, $gateway);

        $entries = app(McpAgentKnowledgeEnricher::class)->enrich(
            new AgentDefinition(
                name: 'Support',
                instructions: 'Help.',
                runtime: [
                    'mcp' => [
                        'server' => 'docs',
                        'resource_uris' => ['file:///policy.md'],
                    ],
                ],
            ),
        );

        $this->assertCount(1, $entries);
        $this->assertStringContainsString('30 days', $entries[0]['content']);
    }

    public function test_mcp_prompts_are_injected_from_agent_runtime_config(): void
    {
        $gateway = new ArrayMcpClientGateway;
        $gateway->registerCatalog('docs', [
            'prompts' => [
                [
                    'name' => 'support-style',
                    'description' => 'Be brief.',
                    'messages' => [
                        ['content' => 'Answer in one short paragraph.'],
                    ],
                ],
            ],
        ]);

        $this->app->instance(McpClientGateway::class, $gateway);

        $entries = app(McpAgentKnowledgeEnricher::class)->enrich(
            new AgentDefinition(
                name: 'Support',
                instructions: 'Help.',
                runtime: [
                    'mcp' => [
                        'server' => 'docs',
                        'prompts' => ['support-style'],
                    ],
                ],
            ),
        );

        $this->assertCount(1, $entries);
        $this->assertStringContainsString('mcp-prompt:docs:support-style', $entries[0]['source']);
        $this->assertStringContainsString('one short paragraph', $entries[0]['content']);
    }
}
