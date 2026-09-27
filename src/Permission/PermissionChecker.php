<?php

namespace Agentic\Permission;

/**
 * Authorization outside the LLM.
 *
 * The LLM may request a Tool; it cannot authorize itself.
 */
interface PermissionChecker
{
    public function allows(string $ability, mixed $subject = null): bool;

    public function denialMessage(string $ability, mixed $subject = null): string;
}
