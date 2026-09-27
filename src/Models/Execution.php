<?php

namespace Agentic\Models;

use Agentic\Execution\ExecutionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Execution extends Model
{
    protected $table = 'agentic_executions';
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
            'failed_at' => 'datetime',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ExecutionStep::class, 'execution_id')->orderBy('id');
    }
}
