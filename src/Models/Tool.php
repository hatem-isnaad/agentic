<?php

namespace Agentic\Models;

use Agentic\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tool extends Model
{
    protected $table = 'agentic_tools';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => Status::class, 'config' => 'array'];
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'agentic_skill_tool', 'tool_id', 'skill_id')
            ->withTimestamps()->orderBy('agentic_skill_tool.position');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ToolVersion::class, 'tool_id')->orderByDesc('version');
    }

    public function publishedVersions(): HasMany
    {
        return $this->hasMany(ToolVersion::class, 'tool_id')
            ->whereNotNull('published_at')->orderByDesc('version');
    }

    public function latestPublishedVersion(): HasOne
    {
        return $this->hasOne(ToolVersion::class, 'tool_id')
            ->whereNotNull('published_at')->latestOfMany('version');
    }

    public function scopePublished($query)
    {
        return $query->where('status', Status::Published);
    }
}
