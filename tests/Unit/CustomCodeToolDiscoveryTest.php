<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Tool\Contracts\DeclarativeCodeToolHandler;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;
use Agentic\Tool\Discovery\CustomCodeToolDiscovery;

final class CustomCodeToolDiscoveryTest extends TestCase
{
    public function test_registers_configured_declarative_class(): void
    {
        $class = DeclarativeStubTool::class;

        config()->set('agentic.code_tools.classes', [$class]);
        config()->set('agentic.code_tools.paths', []);
        config()->set('agentic.code_tools.namespace', '');

        $registry = app(HandlerRegistry::class);
        $discovery = app(CustomCodeToolDiscovery::class);

        $this->assertTrue($discovery->registerDiscovered($registry) >= 1);
        $this->assertTrue($registry->has('tests.stub.tool'));
    }
}

final class DeclarativeStubTool implements DeclarativeCodeToolHandler
{
    public static function handlerName(): string
    {
        return 'tests.stub.tool';
    }

    public static function toolDescription(): string
    {
        return 'Stub';
    }

    public static function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function handle(ToolExecutionContext $context): ToolResult
    {
        return ToolResult::success(['ok' => true]);
    }
}
