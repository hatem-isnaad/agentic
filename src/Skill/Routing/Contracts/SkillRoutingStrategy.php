<?php

namespace Agentic\Skill\Routing\Contracts;

use Agentic\Skill\Routing\SkillRoutingContext;
use Agentic\Skill\Routing\SkillRoutingResult;

interface SkillRoutingStrategy
{
    public function name(): string;

    public function route(SkillRoutingContext $context): ?SkillRoutingResult;
}
