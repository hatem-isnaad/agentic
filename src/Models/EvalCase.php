<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvalCase extends Model
{
    protected $table = 'agentic_eval_cases';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expect_contains' => 'array',
        ];
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(EvalSet::class, 'eval_set_id');
    }
}
