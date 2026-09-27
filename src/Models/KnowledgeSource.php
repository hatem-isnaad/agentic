<?php

namespace Agentic\Models;

use Agentic\Enums\Status;
use Illuminate\Database\Eloquent\Model;

class KnowledgeSource extends Model
{
    protected $table = 'agentic_knowledge_sources';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'config' => 'array',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', Status::Published);
    }
}
