<?php

namespace Agentic\Mcp;

use Agentic\Agent\AgentDefinition;
use Agentic\Tool\Contracts\McpClientGateway;

/**
 * Injects MCP resource text and selected prompts into agent knowledge context.
 *
 * Agent config (JSON):
 * {
 *   "mcp": {
 *     "server": "docs",
 *     "resource_uris": ["file:///policy.md"],
 *     "prompts": ["support-style"]
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
        $mcp = $this->resolveMcpConfig($agent);

        if ($mcp === null) {
            return [];
        }

        $server = (string) ($mcp['server'] ?? '');

        if ($server === '' || ! $this->gateway->hasServer($server)) {
            return [];
        }

        $max = max(1, (int) config('agentic.mcp.max_resource_injections', 5));
        $entries = [];

        if ((bool) config('agentic.mcp.inject_resources', true)) {
            $entries = array_merge($entries, $this->resourceEntries($server, $mcp, $max));
        }

        if ((bool) config('agentic.mcp.inject_prompts', true)) {
            $entries = array_merge($entries, $this->promptEntries($server, $mcp, $max));
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $mcp
     * @return list<array{source: string, content: string}>
     */
    private function resourceEntries(string $server, array $mcp, int $max): array
    {
        $uris = $mcp['resource_uris'] ?? [];
        if (! is_array($uris) || $uris === []) {
            return [];
        }

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
     * @param  array<string, mixed>  $mcp
     * @return list<array{source: string, content: string}>
     */
    private function promptEntries(string $server, array $mcp, int $max): array
    {
        $prompts = $mcp['prompts'] ?? [];
        if (! is_array($prompts) || $prompts === []) {
            return [];
        }

        $entries = [];

        foreach (array_slice($prompts, 0, $max) as $prompt) {
            $name = is_string($prompt) ? $prompt : (string) ($prompt['name'] ?? '');
            $arguments = is_array($prompt) && is_array($prompt['arguments'] ?? null) ? $prompt['arguments'] : [];

            if ($name === '') {
                continue;
            }

            $payload = $this->gateway->getPrompt($server, $name, $arguments);
            $text = $this->promptText($payload);

            if ($text === '') {
                continue;
            }

            $entries[] = [
                'source' => 'mcp-prompt:'.$server.':'.$name,
                'content' => $text,
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function promptText(array $payload): string
    {
        $chunks = [];
        $description = trim((string) ($payload['description'] ?? ''));
        if ($description !== '') {
            $chunks[] = $description;
        }

        foreach ($payload['messages'] ?? [] as $message) {
            if (! is_array($message)) {
                continue;
            }

            $content = $message['content'] ?? '';
            if (is_array($content)) {
                $content = (string) ($content['text'] ?? json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $line = trim((string) $content);
            if ($line !== '') {
                $chunks[] = $line;
            }
        }

        return trim(implode("\n", $chunks));
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
