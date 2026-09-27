<?php

namespace Agentic\Tool;

use Agentic\Models\Tool;
use Agentic\Models\ToolVersion;
use RuntimeException;

final class ToolVersionResolver
{
    public function resolve(Tool $tool): ToolVersion
    {
        $version = $tool->latestPublishedVersion()->first();

        if ($version === null) {
            throw new RuntimeException("Tool [{$tool->slug}] has no published version.");
        }

        return $version;
    }
}
