<?php

namespace Agentic\Models;

use Agentic\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agent extends Model
{
    protected $table = 'agentic_agents';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => Status::class, 'model_config' => 'array', 'config' => 'array'];
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'agentic_agent_skill', 'agent_id', 'skill_id')
            ->withTimestamps()->orderBy('agentic_agent_skill.position');
    }

    public function scopePublished($query)
    {
        return $query->where('status', Status::Published);
    }
}
