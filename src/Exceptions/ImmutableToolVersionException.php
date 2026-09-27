<?php

namespace Agentic\Exceptions;

use RuntimeException;

final class ImmutableToolVersionException extends RuntimeException
{
    public static function forVersion(int $toolId, int $version): self
    {
        return new self("Published tool version [{$toolId}@v{$version}] is immutable.");
    }
}
