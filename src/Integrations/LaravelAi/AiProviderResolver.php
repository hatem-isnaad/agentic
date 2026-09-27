<?php

namespace Agentic\Integrations\LaravelAi;

use RuntimeException;

/**
 * Resolves the Laravel AI SDK provider/model for an agent.
 *
 * Empty strings from admin/DB are treated as unset so AGENTIC_AI_* defaults apply.
 */
final class AiProviderResolver
{
    public static function provider(?string $agentProvider): ?string
    {
        return self::filled($agentProvider) ? $agentProvider : self::stringOrNull(config('agentic.ai.provider'));
    }

    public static function model(?string $agentModel): ?string
    {
        return self::filled($agentModel) ? $agentModel : self::stringOrNull(config('agentic.ai.model'));
    }

    public static function assertReady(?string $provider): void
    {
        if ($provider !== 'anthropic') {
            return;
        }

        if (filled(config('ai.providers.anthropic.key'))) {
            return;
        }

        throw new RuntimeException(
            'Anthropic is selected but ANTHROPIC_API_KEY is empty. Add the key to .env, then restart `php artisan serve` and `php artisan queue:work`.',
        );
    }

    private static function filled(?string $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
