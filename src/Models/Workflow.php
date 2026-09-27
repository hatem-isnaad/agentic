<?php

namespace Agentic\Models;

use Agentic\Enums\Status;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected $table = 'agentic_workflows';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'definition' => 'array',
            'status' => Status::class,
        ];
    }
}
