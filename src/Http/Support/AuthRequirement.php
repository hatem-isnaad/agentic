<?php

namespace Agentic\Http\Support;

final class AuthRequirement
{
    /**
     * Explicit true/false wins. Empty/null stays "auto": required outside local/testing.
     * Package config defaults admin + runtime APIs to true so composer-install is closed.
     */
    public static function enabled(mixed $configured): bool
    {
        if ($configured === null || $configured === '') {
            return ! app()->environment(['local', 'testing']);
        }

        $bool = filter_var($configured, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $bool ?? ! app()->environment(['local', 'testing']);
    }
}
