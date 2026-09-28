<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvalRun extends Model
{
    protected $table = 'agentic_eval_runs';

    protected $guarded = [];

    public function set(): BelongsTo
    {
        return $this->belongsTo(EvalSet::class, 'eval_set_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(EvalResult::class, 'eval_run_id');
    }
}
