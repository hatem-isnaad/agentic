<?php

namespace Agentic\Persistence;

use Agentic\Enums\Status;
use Agentic\Models\Tool;
use Agentic\Models\ToolVersion;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ToolVersionPublisher
{
    public function publish(Tool $tool, array $definition): ToolVersion
    {
        $this->validateDefinition($tool, $definition);

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

    public function archive(Tool $tool): Tool
    {
        return DB::transaction(function () use ($tool): Tool {
            $tool->forceFill(['status' => Status::Archived])->save();

            return $tool->fresh() ?? $tool;
        });
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function validateDefinition(Tool $tool, array $definition): void
    {
        if ($definition === []) {
            throw new InvalidArgumentException("Tool [{$tool->slug}] cannot publish an empty definition.");
        }

        if ($tool->driver === 'http' && ! is_string($definition['url'] ?? null)) {
            throw new InvalidArgumentException("HTTP tool [{$tool->slug}] must include a [url] before publishing.");
        }
    }
}
