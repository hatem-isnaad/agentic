<?php

namespace Agentic\Mcp;

use Agentic\Agent\AgentDefinition;
use Agentic\Tool\Contracts\McpClientGateway;

/**
 * Injects MCP resource text into agent knowledge context when configured on the agent.
 *
 * Agent config (JSON):
 * {
 *   "mcp": {
 *     "server": "docs",
 *     "resource_uris": ["file:///policy.md"]
 *   }
 * }
 */
final class McpAgentKnowledgeEnricher
{
    public function __construct(
        private McpClientGateway $gateway,
    ) {}

    /**
     * @return list<array{source: string, content: string}>
     */
    public function enrich(AgentDefinition $agent): array
    {
        if (! (bool) config('agentic.mcp.inject_resources', true)) {
            return [];
        }

        $mcp = $this->resolveMcpConfig($agent);

        if ($mcp === null) {
            return [];
        }

        $server = (string) ($mcp['server'] ?? '');

        if ($server === '' || ! $this->gateway->hasServer($server)) {
            return [];
        }

        $uris = $mcp['resource_uris'] ?? [];

        if (! is_array($uris) || $uris === []) {
            return [];
        }

        $max = max(1, (int) config('agentic.mcp.max_resource_injections', 5));
        $entries = [];

        foreach (array_slice($uris, 0, $max) as $uri) {
            if (! is_string($uri) || $uri === '') {
                continue;
            }

            $payload = $this->gateway->readResource($server, $uri);
            $text = is_string($payload['text'] ?? null) ? trim($payload['text']) : '';

            if ($text === '') {
                continue;
            }

            $entries[] = [
                'source' => 'mcp:'.$server.':'.$uri,
                'content' => $text,
            ];
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMcpConfig(AgentDefinition $agent): ?array
    {
        $runtimeMcp = $agent->runtime['mcp'] ?? null;

        if (is_array($runtimeMcp)) {
            return $runtimeMcp;
        }

        $config = $agent->metadata['config'] ?? null;

        if (is_array($config) && is_array($config['mcp'] ?? null)) {
            return $config['mcp'];
        }

        return null;
    }
}
