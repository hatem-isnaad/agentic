<?php

namespace Agentic\Permission;

/**
 * Allows tools whose names match configured fnmatch patterns (e.g. orders.*).
 *
 * Deny patterns are evaluated first. When no pattern matches, falls back to
 * agentic.permissions.default (deny | allow).
 */
final class RuleBasedPermissionChecker implements PermissionChecker
{
    public function allows(string $ability, mixed $subject = null): bool
    {
        $tool = str_starts_with($ability, 'tool:') ? substr($ability, 5) : $ability;

        foreach ($this->patterns('deny_patterns') as $pattern) {
            if ($this->matches($pattern, $tool)) {
                return false;
            }
        }

        foreach ($this->patterns('allow_patterns') as $pattern) {
            if ($this->matches($pattern, $tool)) {
                return true;
            }
        }

        return (string) config('agentic.permissions.default', 'deny') === 'allow';
    }

    public function denialMessage(string $ability, mixed $subject = null): string
    {
        $tool = str_starts_with($ability, 'tool:') ? substr($ability, 5) : $ability;

        return str_replace(
            ':tool',
            $tool,
            (string) config('agentic.permissions.denial_message', 'Permission denied for tool [:tool].'),
        );
    }

    /**
     * @return list<string>
     */
    private function patterns(string $key): array
    {
        $patterns = config('agentic.permissions.'.$key, []);

        if (! is_array($patterns)) {
            return [];
        }

        return array_values(array_filter(
            array_map('strval', $patterns),
            fn (string $pattern) => $pattern !== '',
        ));
    }

    private function matches(string $pattern, string $tool): bool
    {
        return $pattern === '*' || fnmatch($pattern, $tool);
    }
}
