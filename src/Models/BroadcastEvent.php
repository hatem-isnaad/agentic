<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

final class BroadcastEvent extends Model
{
    public $timestamps = false;

    protected $table = 'agentic_broadcast_events';

    protected $fillable = [
        'channel',
        'event',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
