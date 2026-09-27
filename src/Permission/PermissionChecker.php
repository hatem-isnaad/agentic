<?php

namespace Agentic\Permission;

interface PermissionChecker
{
    public function allows(string $ability, mixed $subject = null): bool;
}
