<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelAccount extends Model
{
    protected $table = 'agentic_channel_accounts';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['config' => 'array'];
    }

    public function setCredentialsAttribute(mixed $value): void
    {
        $this->attributes['credentials'] = $value === null
            ? null
            : encrypt(is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR));
    }

    public function getCredentialsAttribute(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            $decoded = decrypt($value);

            return json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        $bag = is_array($this->credentials) ? $this->credentials : [];

        return $bag[$key] ?? $default;
    }

    public function configValue(string $key, mixed $default = null): mixed
    {
        $bag = is_array($this->config) ? $this->config : [];

        return $bag[$key] ?? $default;
    }
}
