<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

final class WidgetEmbedToken extends Model
{
    protected $table = 'agentic_widget_embed_tokens';

    protected $fillable = [
        'name',
        'token_prefix',
        'token_hash',
        'allowed_agents',
        'allowed_origins',
        'guest_allowed',
        'sanctum_allowed',
        'enabled',
        'expires_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'allowed_agents' => 'array',
            'allowed_origins' => 'array',
            'guest_allowed' => 'bool',
            'sanctum_allowed' => 'bool',
            'enabled' => 'bool',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * @return list<string>|null null = any published agent
     */
    public function agentAllowList(): ?array
    {
        $list = $this->allowed_agents;

        if ($list === null || $list === []) {
            return null;
        }

        return array_values(array_filter(array_map('strval', $list)));
    }

    public function allowsAgent(string $slug): bool
    {
        $list = $this->agentAllowList();

        return $list === null || in_array($slug, $list, true);
    }

    public function allowsOrigin(?string $origin): bool
    {
        $list = $this->allowed_origins;

        if ($list === null || $list === []) {
            return true;
        }

        if ($origin === null || $origin === '') {
            return false;
        }

        foreach ($list as $allowed) {
            if (strcasecmp((string) $allowed, $origin) === 0) {
                return true;
            }
        }

        return false;
    }
}
