<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRun extends Model
{
    protected $table = 'agentic_workflow_runs';

    protected $fillable = [
        'uuid',
        'workflow_slug',
        'status',
        'step_pointer',
        'variables',
        'trace',
        'approval_uuid',
        'output',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'trace' => 'array',
            'output' => 'array',
        ];
    }
}
