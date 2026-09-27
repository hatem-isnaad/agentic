<?php

namespace Agentic\Persistence;

use Agentic\Enums\Status;
use Agentic\Models\Tool;
use Agentic\Models\ToolVersion;
use Illuminate\Support\Facades\DB;

final class ToolVersionPublisher
{
    public function publish(Tool $tool, array $definition): ToolVersion
    {
        return DB::transaction(function () use ($tool, $definition): ToolVersion {
            $nextVersion = ((int) $tool->versions()->max('version')) + 1;

            $version = $tool->versions()->create([
                'version' => $nextVersion,
                'definition' => $definition,
                'published_at' => now(),
            ]);

            $tool->forceFill(['status' => Status::Published])->save();

            return $version;
        });
    }
}
