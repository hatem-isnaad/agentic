<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvalSet extends Model
{
    protected $table = 'agentic_eval_sets';

    protected $guarded = [];

    public function cases(): HasMany
    {
        return $this->hasMany(EvalCase::class, 'eval_set_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(EvalRun::class, 'eval_set_id')->latest('id');
    }
}
