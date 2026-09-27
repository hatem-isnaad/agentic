<?php

namespace Agentic\Tests\Unit;

use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Models\Tool;
use Agentic\Models\ToolVersion;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Tests\TestCase;
use Agentic\Tool\ToolDefinition;
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

    public function test_it_resolves_the_immutable_version_row_id_from_a_definition(): void
    {
        $tool = Tool::create([
            'name' => 'Orders',
            'slug' => 'orders.get',
            'type' => 'http',
            'driver' => 'http',
        ]);

        ToolVersion::create([
            'tool_id' => $tool->id,
            'version' => 1,
            'definition' => ['endpoint' => '/v1/orders'],
            'published_at' => now(),
        ]);

        ToolVersion::create([
            'tool_id' => $tool->id,
            'version' => 2,
            'definition' => ['endpoint' => '/v2/orders'],
            'published_at' => now(),
        ]);

        $definition = new ToolDefinition(
            name: 'orders.get',
            description: 'Get orders',
            version: 1,
            id: $tool->id,
        );

        $id = app(ToolVersionResolver::class)->resolveId($definition);

        $this->assertSame(
            ToolVersion::query()
                ->where('tool_id', $tool->id)
                ->where('version', 1)
                ->value('id'),
            $id,
        );
    }
}
