<?php

namespace Agentic\Tests\Unit;

use Agentic\Models\Tool;
use Agentic\Tests\TestCase;
use Agentic\Tool\ToolVersionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

final class ToolVersionResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_tools_without_a_published_version(): void
    {
        $tool = Tool::create([
            'name' => 'Draft Tool',
            'slug' => 'draft-tool',
            'type' => 'http',
            'driver' => 'http',
        ]);

        $this->expectException(RuntimeException::class);

        app(ToolVersionResolver::class)->resolve($tool);
    }
}
