<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $table = 'agentic_connections';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
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
        } catch (Throwable) {
            return $value;
        }
    }
}
