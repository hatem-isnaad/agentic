<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

class Memory extends Model
{
    protected $table = 'agentic_memories';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
