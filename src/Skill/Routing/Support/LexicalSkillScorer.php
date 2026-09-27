<?php

namespace Agentic\Skill\Routing\Support;

use Agentic\Knowledge\Support\CosineSimilarity;
use Agentic\Skill\SkillDefinition;

/**
 * Lightweight lexical similarity between a user message and skill text (no embeddings API).
 */
final class LexicalSkillScorer
{
    /**
     * @param  list<SkillDefinition>  $skills
     * @return array<string, float>  skill name => score 0..1
     */
    public function scoreMessage(string $message, array $skills): array
    {
        $messageTokens = $this->tokenize($message);

        if ($messageTokens === []) {
            return [];
        }

        $scores = [];

        foreach ($skills as $skill) {
            $corpus = $this->skillCorpus($skill);
            $corpusTokens = $this->tokenize($corpus);

            if ($corpusTokens === []) {
                continue;
            }

            $scores[$skill->name] = $this->cosineFromTokenLists($messageTokens, $corpusTokens);
        }

        return $scores;
    }

    private function skillCorpus(SkillDefinition $skill): string
    {
        $parts = [
            $skill->name,
            $skill->description,
        ];

        $keywords = $skill->metadata['routing_keywords']
            ?? $skill->metadata['keywords']
            ?? [];

        if (is_array($keywords)) {
            $parts = [...$parts, ...array_filter($keywords, 'is_string')];
        }

        foreach ($skill->tools as $tool) {
            $parts[] = $tool;
        }

        return implode(' ', array_filter($parts, fn ($part) => is_string($part) && $part !== ''));
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $normalized = mb_strtolower($text);
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? $normalized;
        $parts = preg_split('/\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts)) {
            return [];
        }

        $tokens = [];

        foreach ($parts as $part) {
            $tokens[] = $part;
            $stem = $this->stemToken($part);

            if ($stem !== $part) {
                $tokens[] = $stem;
            }
        }

        return array_values($tokens);
    }

    private function stemToken(string $token): string
    {
        if (strlen($token) > 4 && str_ends_with($token, 's') && ! str_ends_with($token, 'ss')) {
            return substr($token, 0, -1);
        }

        return $token;
    }

    /**
     * @param  list<string>  $left
     * @param  list<string>  $right
     */
    private function cosineFromTokenLists(array $left, array $right): float
    {
        $leftCounts = array_count_values($left);
        $rightCounts = array_count_values($right);
        $vocabulary = array_values(array_unique([...array_keys($leftCounts), ...array_keys($rightCounts)]));

        $leftVector = [];
        $rightVector = [];

        foreach ($vocabulary as $term) {
            $leftVector[] = (float) ($leftCounts[$term] ?? 0);
            $rightVector[] = (float) ($rightCounts[$term] ?? 0);
        }

        return CosineSimilarity::score($leftVector, $rightVector);
    }
}
