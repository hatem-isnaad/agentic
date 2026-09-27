<?php

namespace Agentic\Tests\Unit;

use Agentic\Enums\Status;
use Agentic\Models\Tool;
use Agentic\Persistence\ToolVersionPublisher;
use Agentic\Tests\TestCase;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

final class ToolFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_registers_persisted_http_tool_into_registry(): void
    {
        Http::fake([
            'https://api.example.test/ping' => Http::response(['pong' => true], 200),
        ]);

        $tool = Tool::create([
            'name' => 'Ping',
            'slug' => 'ping',
            'description' => 'Ping API',
            'type' => 'http',
            'driver' => 'http',
            'status' => Status::Draft,
        ]);

        app(ToolVersionPublisher::class)->publish($tool, [
            'method' => 'GET',
            'url' => 'https://api.example.test/ping',
            'input_schema' => [],
        ]);

        $contract = app(ToolFactory::class)->registerFromSlug('ping');

        $this->assertTrue(app(ToolRegistry::class)->has('ping'));
        $this->assertSame('http', $contract->definition()->driver);

        $result = $contract->execute(new \Agentic\Tool\ToolExecutionContext());

        $this->assertTrue($result->success);
        $this->assertSame(['pong' => true], $result->data);
    }
}
