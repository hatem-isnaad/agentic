<?php

namespace Agentic\Skill\Routing\Strategies;

use Agentic\Skill\Routing\Contracts\SkillRoutingStrategy;
use Agentic\Skill\Routing\SkillRoutingContext;
use Agentic\Skill\Routing\SkillRoutingResult;
use Agentic\Skill\Routing\Support\SkillLlmSelector;

/**
 * Optional LLM skill routing — register after deterministic strategies.
 */
final class LlmSkillRoutingStrategy implements SkillRoutingStrategy
{
    public function __construct(
        private SkillLlmSelector $selector,
        private ?string $provider = null,
        private ?string $model = null,
        private float $minConfidence = 0.25,
    ) {}

    public function name(): string
    {
        return 'llm';
    }

    public function route(SkillRoutingContext $context): ?SkillRoutingResult
    {
        $selected = $this->selector->select(
            $context->message,
            $context->candidateSkills,
            $this->provider,
            $this->model,
        );

        if ($selected === []) {
            return null;
        }

        $confidence = min(1.0, count($selected) / max(1, count($context->candidateSkills)));

        if ($confidence < $this->minConfidence) {
            return null;
        }

        return new SkillRoutingResult(
            skills: $selected,
            strategy: $this->name(),
            confidence: $confidence,
            metadata: ['model' => $this->model ?? config('agentic.ai.model')],
        );
    }
}
