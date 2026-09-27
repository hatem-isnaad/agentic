<?php

namespace Agentic\Skill\Routing\Support;

use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillResolver;
use Laravel\Ai\Contracts\Agent as LaravelAgent;

use function Laravel\Ai\agent;

/**
 * Optional LLM pass: pick skill names from a candidate list (never invent names).
 */
final class SkillLlmSelector
{
    public function __construct(
        private SkillResolver $skills,
    ) {}

    /**
     * @param  list<string>  $candidateSkillNames
     * @return list<string>
     */
    public function select(
        string $message,
        array $candidateSkillNames,
        ?string $provider = null,
        ?string $model = null,
    ): array {
        if ($message === '' || $candidateSkillNames === []) {
            return [];
        }

        $definitions = [];

        foreach ($candidateSkillNames as $name) {
            try {
                $definitions[] = $this->skills->resolve($name);
            } catch (\Throwable) {
                continue;
            }
        }

        if ($definitions === []) {
            return [];
        }

        $catalog = array_map(
            fn (SkillDefinition $skill): array => [
                'name' => $skill->name,
                'description' => $skill->description,
            ],
            $definitions,
        );

        $instructions = <<<'PROMPT'
You route user messages to the smallest useful set of skills for a support agent.
Reply with ONLY valid JSON: a JSON array of skill "name" strings chosen from the catalog.
Pick one or more skills when relevant; pick none if the catalog does not apply.
Never invent skill names not listed in the catalog.
PROMPT;

        $prompt = "Catalog:\n".json_encode($catalog, JSON_THROW_ON_ERROR)
            ."\n\nUser message:\n".$message;

        /** @var LaravelAgent $sdkAgent */
        $sdkAgent = agent(instructions: $instructions);

        $response = $sdkAgent->prompt(
            $prompt,
            provider: $provider ?? config('agentic.ai.provider'),
            model: $model ?? config('agentic.ai.model'),
        );

        return $this->parseSkillNames((string) $response->text, $candidateSkillNames);
    }

    /**
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function parseSkillNames(string $text, array $allowed): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $text) ?? $text;
        }

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        $names = [];

        foreach ($decoded as $item) {
            if (is_string($item) && in_array($item, $allowed, true)) {
                $names[] = $item;
            }
        }

        return array_values(array_unique($names));
    }
}
