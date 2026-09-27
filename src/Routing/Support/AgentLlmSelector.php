<?php

namespace Agentic\Routing\Support;

use Laravel\Ai\Contracts\Agent as LaravelAgent;

use function Laravel\Ai\agent;

/**
 * Optional LLM pass to pick one agent slug from allowed candidates.
 */
final class AgentLlmSelector
{
    /**
     * @param  list<array{slug: string, label: string, description?: string}>  $candidates
     */
    public function select(
        string $message,
        array $candidates,
        ?string $provider = null,
        ?string $model = null,
    ): ?string {
        if ($message === '' || $candidates === []) {
            return null;
        }

        $allowed = array_column($candidates, 'slug');

        $instructions = <<<'PROMPT'
You route incoming messages to the best agent.
Reply with ONLY valid JSON: {"agent":"<slug>"} using a slug from the catalog.
If none fit, reply {"agent":null}.
Never invent slugs not in the catalog.
PROMPT;

        $prompt = "Catalog:\n".json_encode($candidates, JSON_THROW_ON_ERROR)
            ."\n\nUser message:\n".$message;

        /** @var LaravelAgent $sdkAgent */
        $sdkAgent = agent(instructions: $instructions);

        $response = $sdkAgent->prompt(
            $prompt,
            provider: $provider ?? config('agentic.ai.provider'),
            model: $model ?? config('agentic.ai.model'),
        );

        return $this->parseAgentSlug((string) $response->text, $allowed);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function parseAgentSlug(string $text, array $allowed): ?string
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $text) ?? $text;
        }

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $agent = $decoded['agent'] ?? null;

        if (! is_string($agent) || $agent === '') {
            return null;
        }

        return in_array($agent, $allowed, true) ? $agent : null;
    }
}
