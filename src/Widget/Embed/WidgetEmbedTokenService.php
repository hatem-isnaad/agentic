<?php

namespace Agentic\Widget\Embed;

use Agentic\Models\WidgetEmbedToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class WidgetEmbedTokenService
{
    /**
     * @param  list<string>|null  $allowedAgents
     * @param  list<string>|null  $allowedOrigins
     * @return array{token: WidgetEmbedToken, plain: string}
     */
    public function create(
        string $name,
        ?array $allowedAgents = null,
        ?array $allowedOrigins = null,
        bool $guestAllowed = true,
        bool $sanctumAllowed = true,
        ?\DateTimeInterface $expiresAt = null,
    ): array {
        $plain = 'wgt_'.Str::random(48);
        $prefix = substr($plain, 0, 12);

        $token = WidgetEmbedToken::query()->create([
            'name' => $name,
            'token_prefix' => $prefix,
            'token_hash' => Hash::make($plain),
            'allowed_agents' => $allowedAgents,
            'allowed_origins' => $allowedOrigins,
            'guest_allowed' => $guestAllowed,
            'sanctum_allowed' => $sanctumAllowed,
            'enabled' => true,
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'plain' => $plain];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(WidgetEmbedToken $token, array $attributes): WidgetEmbedToken
    {
        $fillable = array_intersect_key($attributes, array_flip([
            'name',
            'allowed_agents',
            'allowed_origins',
            'guest_allowed',
            'sanctum_allowed',
            'enabled',
            'expires_at',
        ]));

        $token->fill($fillable)->save();

        return $token->fresh() ?? $token;
    }

    public function revoke(WidgetEmbedToken $token): void
    {
        $token->forceFill(['enabled' => false])->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(WidgetEmbedToken $token): array
    {
        return [
            'id' => $token->id,
            'name' => $token->name,
            'token_prefix' => $token->token_prefix,
            'allowed_agents' => $token->allowed_agents,
            'allowed_origins' => $token->allowed_origins,
            'guest_allowed' => $token->guest_allowed,
            'sanctum_allowed' => $token->sanctum_allowed,
            'enabled' => $token->enabled,
            'expires_at' => $token->expires_at?->toISOString(),
            'last_used_at' => $token->last_used_at?->toISOString(),
            'created_at' => $token->created_at?->toISOString(),
        ];
    }
}
