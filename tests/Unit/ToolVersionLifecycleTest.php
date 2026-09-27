<?php

namespace Agentic\Tests\Unit;

use Agentic\Enums\Status;
use Agentic\Exceptions\ImmutableToolVersionException;
use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Models\Tool;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

final class ToolVersionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_versions_cannot_be_mutated_or_deleted(): void
    {
        $tool = Tool::create([
            'name' => 'Orders',
            'slug' => 'orders.get',
            'type' => 'http',
            'driver' => 'http',
        ]);

        $version = app(ToolVersionPublisher::class)->publish($tool, [
            'method' => 'GET',
            'url' => 'https://api.example.test/orders',
        ]);

        $this->expectException(ImmutableToolVersionException::class);
        $version->update(['definition' => ['method' => 'DELETE']]);
    }

    public function test_http_tools_require_url_before_publish(): void
    {
        $tool = Tool::create([
            'name' => 'Orders',
            'slug' => 'orders.get',
            'type' => 'http',
            'driver' => 'http',
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ToolVersionPublisher::class)->publish($tool, ['method' => 'GET']);
    }

    public function test_archive_marks_tool_without_deleting_versions(): void
    {
        $tool = Tool::create([
            'name' => 'Orders',
            'slug' => 'orders.get',
            'type' => 'http',
            'driver' => 'http',
        ]);

        app(ToolVersionPublisher::class)->publish($tool, [
            'method' => 'GET',
            'url' => 'https://api.example.test/orders',
        ]);

        $archived = app(ToolVersionPublisher::class)->archive($tool->fresh());

        $this->assertSame(Status::Archived, $archived->status);
        $this->assertSame(1, $tool->versions()->count());
    }

    public function test_resolver_can_load_a_specific_published_version(): void
    {
        $tool = Tool::create([
            'name' => 'Orders',
            'slug' => 'orders.get',
            'type' => 'http',
            'driver' => 'http',
        ]);

        app(ToolVersionPublisher::class)->publish($tool, [
            'method' => 'GET',
            'url' => 'https://api.example.test/v1/orders',
        ]);

        app(ToolVersionPublisher::class)->publish($tool->fresh(), [
            'method' => 'GET',
            'url' => 'https://api.example.test/v2/orders',
        ]);

        $v1 = app(ToolVersionResolver::class)->resolveVersion($tool->fresh(), 1);

        $this->assertSame(1, $v1->version);
        $this->assertSame('https://api.example.test/v1/orders', $v1->definition['url']);

        $this->expectException(ToolNotFoundException::class);
        app(ToolVersionResolver::class)->resolveVersion($tool, 99);
    }
}
