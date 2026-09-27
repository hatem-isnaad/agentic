<?php

namespace Agentic\Tests\Unit;

use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Models\Tool;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

        $this->expectException(ToolNotFoundException::class);

        app(ToolVersionResolver::class)->resolve($tool);
    }
}
