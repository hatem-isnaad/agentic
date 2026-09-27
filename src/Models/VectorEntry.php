<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

class VectorEntry extends Model
{
    protected $table = 'agentic_vector_entries';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'vector' => 'array',
            'metadata' => 'array',
        ];
    }
}
