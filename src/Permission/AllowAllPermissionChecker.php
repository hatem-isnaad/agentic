<?php

namespace Agentic\Permission;

final class AllowAllPermissionChecker implements PermissionChecker
{
    public function allows(string $ability, mixed $subject = null): bool
    {
        return true;
    }
}
