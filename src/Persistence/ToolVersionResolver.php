<?php

namespace Agentic\Persistence;

use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Models\Tool;
use Agentic\Models\ToolVersion;

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
}
