<?php

namespace Agentic\Events;

final class PermissionChecked
{
    public function __construct(
        public string $tool,
        public bool $allowed,
        public ?string $agent = null,
    ) {}
}
