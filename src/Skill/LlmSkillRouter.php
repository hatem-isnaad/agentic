<?php

namespace Agentic\Skill;

use Laravel\Ai\Classification;
use Laravel\Ai\Classification\Choice;
use Laravel\Ai\Responses\Data\ChoiceAnswer;

/**
 * Uses Laravel AI SDK classification as a fallback when deterministic
 * skill keyword routing cannot identify a skill.
 */
final class LlmSkillRouter
{
    public function __construct(
        private SkillResolver $skills,
    ) {}

    public function select(array $skillNames, string $message): ?SkillSelection
    {
        $candidates = $this->skills->resolveMany($skillNames);

        if ($message === '' || $candidates === []) {
            return null;
        }

        $options = [];

        foreach ($candidates as $skill) {
            $description = trim($skill->description);

            if ($description === '') {
                $description = 'Skill: '.$skill->name;
            }

            $options[$skill->name] = $description;
        }

        if (count($options) < 2) {
            return null;
        }

        $classification = Classification::of($message)
            ->questions([
                'skill' => new Choice(
                    'Which single skill best matches the user request?',
                    $options,
                ),
            ]);

        $provider = config('agentic.skill_routing.ai.provider');
        $model = config('agentic.skill_routing.ai.model');

        try {
            if (is_string($provider) && $provider !== '' && is_string($model) && $model !== '') {
                $response = $classification->classify(provider: $provider, model: $model);
            } elseif (is_string($provider) && $provider !== '') {
                $response = $classification->classify(provider: $provider);
            } else {
                $response = $classification->classify();
            }
        } catch (\Throwable) {
            return null;
        }

        $answer = $response->answer('skill');

        if (! $answer instanceof ChoiceAnswer) {
            return null;
        }

        $confidence = $answer->confidence;
        $threshold = (float) config('agentic.skill_routing.ai.min_confidence', 0.75);

        if ($confidence === null || $confidence < $threshold || ! isset($options[$answer->choice])) {
            return null;
        }

        return new SkillSelection(
            skills: [$answer->choice],
            matches: [$answer->choice => ['ai']],
        );
    }
}
