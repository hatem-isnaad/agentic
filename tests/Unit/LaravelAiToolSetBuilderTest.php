<?php

namespace Agentic\Tests\Unit;

use Laravel\Ai\Contracts\Tool;
use Agentic\Integrations\LaravelAi\LaravelAiToolSetBuilder;
use Agentic\Tests\TestCase;
use Laravel\Ai\Providers\Tools\ToolSearch;

final class LaravelAiToolSetBuilderTest extends TestCase
{
    public function test_openai_tools_can_be_deferred(): void
    {
        config()->set('agentic.ai.deferred_tools', [
            'enabled' => true,
            'deferred_count' => 2,
            'direct_tools' => 0,
            'strategy' => null,
        ]);

        $tools = array_map(
            fn (int $i) => $this->createMock(Tool::class),
            range(1, 3),
        );

        $result = app(LaravelAiToolSetBuilder::class)->build($tools, 'openai');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(ToolSearch::class, $result[0]);
        $this->assertCount(3, $result[0]->tools);
    }

    public function test_anthropic_keeps_one_direct_tool(): void
    {
        config()->set('agentic.ai.deferred_tools', [
            'enabled' => true,
            'deferred_count' => 1,
            'direct_tools' => 1,
            'strategy' => 'bm25',
        ]);

        $tools = array_map(
            fn (int $i) => $this->createMock(AgenticLaravelTool::class),
            range(1, 3),
        );

        $result = app(LaravelAiToolSetBuilder::class)->build($tools, 'anthropic');

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Tool::class, $result[0]);
        $this->assertInstanceOf(ToolSearch::class, $result[1]);
        $this->assertCount(2, $result[1]->tools);
        $this->assertSame('bm25', $result[1]->strategy);
    }

    public function test_unsupported_provider_keeps_all_tools(): void
    {
        config()->set('agentic.ai.deferred_tools.enabled', true);

        $tools = array_map(
            fn (int $i) => $this->createMock(AgenticLaravelTool::class),
            range(1, 3),
        );

        $result = app(LaravelAiToolSetBuilder::class)->build($tools, 'gemini');

        $this->assertCount(3, $result);
        $this->assertNotContainsOnly(ToolSearch::class, $result);
    }
}
