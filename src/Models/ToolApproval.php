<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

final class ToolApproval extends Model
{
    protected $table = 'agentic_tool_approvals';

    protected $fillable = [
        'uuid',
        'execution_uuid',
        'conversation_uuid',
        'agent',
        'tool',
        'arguments',
        'status',
        'resolved_by',
        'resolved_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'metadata' => 'array',
            'resolved_at' => 'datetime',
        ];
    }
}
