<?php

namespace Agentic\Models;

use Agentic\Execution\ExecutionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionStep extends Model
{
    protected $table = 'agentic_execution_steps';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ExecutionStatus::class,
            'input' => 'array',
            'output' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class, 'execution_id');
    }
}
