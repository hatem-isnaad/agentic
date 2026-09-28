<?php

namespace Agentic\Context;

/**
 * Builds the system prompt from context without dumping JSON blobs.
 */
final class LlmInstructionComposer
{
    public function __construct(private LlmInputCompactor $compactor) {}

    /**
     * @param  array{
     *     instructions?: string,
     *     skills?: list<array{name?: string, description?: string, tools?: list<string>}>,
     *     knowledge?: list<mixed>,
     *     memory?: list<mixed>
     * }  $builtContext
     */
    public function compose(array $builtContext, string $fallbackInstructions = ''): string
    {
        $instructions = trim((string) ($builtContext['instructions'] ?? ''));
        $parts = [
            $instructions !== '' ? $instructions : trim($fallbackInstructions),
        ];

        $skills = $this->compactor->skillBlock($builtContext['skills'] ?? []);
        if ($skills !== '') {
            $parts[] = $skills;
        }

        $knowledge = $this->compactor->knowledgeBlock($builtContext['knowledge'] ?? []);
        if ($knowledge !== '') {
            $parts[] = $knowledge;
        }

        $memory = $this->compactor->memoryBlock($builtContext['memory'] ?? []);
        if ($memory !== '') {
            $parts[] = $memory;
        }

        return implode("\n\n", array_values(array_filter($parts, fn (string $part) => $part !== '')));
    }
}
