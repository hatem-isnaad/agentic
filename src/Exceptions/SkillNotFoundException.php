<?php

namespace Agentic\Exceptions;

final class SkillNotFoundException extends AgenticException
{
    public function __construct(string $identifier)
    {
        parent::__construct("Skill [{$identifier}] was not found.");
    }
}
