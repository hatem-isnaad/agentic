<?php

namespace Agentic\Skill\Routing;

use Agentic\Skill\Routing\Contracts\SkillRoutingStrategy;

/**
 * Selects which skills (and thus tools) are exposed to the LLM for one agent run.
 *
 * Deterministic strategies run first; optional LLM strategy should be registered last.
 */
final class SkillRouter
{
    /** @var list<SkillRoutingStrategy> */
    private array $strategies = [];

    public function __construct(
        private string $fallback = 'all',
    ) {}

    public function use(SkillRoutingStrategy $strategy): self
    {
        $this->strategies[] = $strategy;

        return $this;
    }

    /**
     * @param  list<SkillRoutingStrategy>  $strategies
     */
    public function strategies(array $strategies): self
    {
        foreach ($strategies as $strategy) {
            $this->use($strategy);
        }

        return $this;
    }

    public function route(SkillRoutingContext $context): SkillRoutingResult
    {
        foreach ($this->strategies as $strategy) {
            $result = $strategy->route($context);

            if ($result !== null && $result->skills !== []) {
                return $this->mergeAlwaysOn($context, $result);
            }
        }

        return $this->fallbackResult($context);
    }

    private function mergeAlwaysOn(SkillRoutingContext $context, SkillRoutingResult $result): SkillRoutingResult
    {
        $alwaysOn = $this->alwaysOnSkills($context);
        $skills = array_values(array_unique([...$alwaysOn, ...$result->skills]));

        return new SkillRoutingResult(
            skills: $skills,
            strategy: $result->strategy,
            confidence: $result->confidence,
            metadata: array_merge($result->metadata, ['always_on' => $alwaysOn]),
        );
    }

    private function fallbackResult(SkillRoutingContext $context): SkillRoutingResult
    {
        $skills = match ($this->fallback) {
            'none' => [],
            'first' => $context->candidateSkills !== [] ? [$context->candidateSkills[0]] : [],
            'core' => $this->alwaysOnSkills($context),
            default => $context->candidateSkills,
        };

        return new SkillRoutingResult(
            skills: $skills,
            strategy: 'fallback:'.$this->fallback,
            confidence: $skills === [] ? 0.0 : 0.5,
        );
    }

    /**
     * @return list<string>
     */
    private function alwaysOnSkills(SkillRoutingContext $context): array
    {
        $fromAgent = $context->agent->runtime['core_skills'] ?? [];
        $fromConfig = config('agentic.skill_routing.always_on', []);

        $names = array_merge(
            is_array($fromAgent) ? $fromAgent : [],
            is_array($fromConfig) ? $fromConfig : [],
        );

        return array_values(array_unique(array_intersect(
            $context->candidateSkills,
            array_filter($names, fn ($n) => is_string($n) && $n !== ''),
        )));
    }
}
