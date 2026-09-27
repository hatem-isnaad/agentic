<?php

namespace Agentic\Permission;

enum PermissionDecision: string
{
    case Allow = 'allow';
    case Deny = 'deny';

    public function allowed(): bool
    {
        return $this === self::Allow;
    }
}
