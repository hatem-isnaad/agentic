<?php

namespace Agentic\Permission;

final class DenyAllPermissionChecker implements PermissionChecker
{
    public function allows(string $ability, mixed $subject = null): bool
    {
        return false;
    }

    public function denialMessage(string $ability, mixed $subject = null): string
    {
        return str_replace(
            ':tool',
            $ability,
            (string) config('agentic.permissions.denial_message', 'Permission denied for tool [:tool].'),
        );
    }
}
