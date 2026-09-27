<?php

namespace Agentic\Models;

use Agentic\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $table = 'agentic_skills';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => Status::class, 'config' => 'array'];
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agentic_agent_skill', 'skill_id', 'agent_id')
            ->withTimestamps()->orderBy('agentic_agent_skill.position');
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'agentic_skill_tool', 'skill_id', 'tool_id')
            ->withTimestamps()->orderBy('agentic_skill_tool.position');
    }

    public function scopePublished($query)
    {
        return $query->where('status', Status::Published);
    }
}
