<?php

namespace Agentic\Persistence;

use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Models\Tool;
use Agentic\Models\ToolVersion;
use Agentic\Tool\ToolDefinition;
use Illuminate\Support\Facades\DB;

final class ToolVersionResolver
{
    public function resolve(Tool $tool): ToolVersion
    {
        $version = $tool->latestPublishedVersion()->first();

        if ($version === null) {
            throw new ToolNotFoundException($tool->slug.'@published');
        }

        return $version;
    }

    public function resolveId(ToolDefinition $definition): ?int
    {
        if ($definition->id === null || $definition->version === null) {
            return null;
        }

        return DB::table('agentic_tool_versions')
            ->where('tool_id', $definition->id)
            ->where('version', $definition->version)
            ->whereNotNull('published_at')
            ->value('id');
    }
}
