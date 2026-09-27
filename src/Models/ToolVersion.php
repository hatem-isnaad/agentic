<?php

namespace Agentic\Models;

use Agentic\Exceptions\ImmutableToolVersionException;
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

    protected static function booted(): void
    {
        static::updating(function (ToolVersion $version): void {
            if ($version->getOriginal('published_at') === null) {
                return;
            }

            $definitionChanged = $version->isDirty('definition');
            $versionNumberChanged = $version->isDirty('version');
            $publishStateChanged = $version->isDirty('published_at');

            if ($definitionChanged || $versionNumberChanged || $publishStateChanged) {
                throw ImmutableToolVersionException::forVersion(
                    (int) $version->tool_id,
                    (int) $version->getOriginal('version'),
                );
            }
        });

        static::deleting(function (ToolVersion $version): void {
            if ($version->published_at !== null) {
                throw ImmutableToolVersionException::forVersion(
                    (int) $version->tool_id,
                    (int) $version->version,
                );
            }
        });
    }
}
