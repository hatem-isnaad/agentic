<?php

namespace Agentic\Integrations\LaravelAi;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Providers\Tools\ToolSearch;

final class LaravelAiToolSetBuilder
{
    /**
     * @param  list<Tool>  $tools
     * @return list<Tool>
     */
    public function build(array $tools, ?string $provider = null): array
    {
        $config = (array) config('agentic.ai.deferred_tools', []);

        if (! ($config['enabled'] ?? false) || count($tools) <= 1) {
            return $tools;
        }

        $provider = strtolower((string) $provider);

        if (! in_array($provider, ['openai', 'anthropic'], true)) {
            return $tools;
        }

        $deferredCount = max(1, (int) ($config['deferred_count'] ?? 10));

        if (count($tools) <= $deferredCount) {
            return $tools;
        }

        $directCount = $provider === 'anthropic'
            ? max(1, (int) ($config['direct_tools'] ?? 1))
            : max(0, (int) ($config['direct_tools'] ?? 0));

        $direct = array_slice($tools, 0, $directCount);
        $deferred = array_slice($tools, $directCount);

        if ($deferred === []) {
            return $tools;
        }

        $strategy = $config['strategy'] ?? null;

        $search = new ToolSearch(
            tools: $deferred,
            strategy: is_string($strategy) && $strategy !== '' ? $strategy : null,
        );

        return [...$direct, $search];
    }
}
