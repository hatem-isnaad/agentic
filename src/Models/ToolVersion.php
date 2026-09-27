<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolVersion extends Model
{
    protected $table = 'agentic_tool_versions';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['definition' => 'array', 'published_at' => 'datetime'];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'tool_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
