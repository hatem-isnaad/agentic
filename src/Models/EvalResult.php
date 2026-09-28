<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvalResult extends Model
{
    protected $table = 'agentic_eval_results';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(EvalRun::class, 'eval_run_id');
    }
}
