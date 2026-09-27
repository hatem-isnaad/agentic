<?php

namespace Agentic\Tests\Unit;

use Agentic\Tool\Contracts\CodeToolHandler;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolFactory;
use Agentic\Tool\ToolResult;
use Agentic\Tests\TestCase;
use Agentic\Workflow\WorkflowDefinition;
use Agentic\Workflow\WorkflowRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WorkflowRunnerTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.permissions.checker', \Agentic\Permission\AllowAllPermissionChecker::class);
    }

    public function test_set_and_complete_steps_produce_output(): void
    {
        $result = app(WorkflowRunner::class)->run(
            new WorkflowDefinition(
                slug: 'greet',
                name: 'Greet',
                steps: [
                    ['id' => 'seed', 'type' => 'set', 'variables' => ['name' => '{input.name}']],
                    ['id' => 'done', 'type' => 'complete', 'output' => ['message' => 'Hello {name}']],
                ],
            ),
            ['name' => 'Hatem'],
        );

        $this->assertTrue($result->success);
        $this->assertSame(['message' => 'Hello Hatem'], $result->output);
    }

    public function test_condition_step_branches_to_target_step(): void
    {
        $result = app(WorkflowRunner::class)->run(
            new WorkflowDefinition(
                slug: 'branch',
                name: 'Branch',
                steps: [
                    ['id' => 'seed', 'type' => 'set', 'variables' => ['status' => '{input.status}']],
                    [
                        'id' => 'check',
                        'type' => 'condition',
                        'when' => ['var' => 'status', 'equals' => 'vip'],
                        'goto' => 'vip_done',
                        'else' => 'regular_done',
                    ],
                    ['id' => 'regular_done', 'type' => 'complete', 'output' => ['tier' => 'regular']],
                    ['id' => 'vip_done', 'type' => 'complete', 'output' => ['tier' => 'vip']],
                ],
            ),
            ['status' => 'vip'],
        );

        $this->assertTrue($result->success);
        $this->assertSame(['tier' => 'vip'], $result->output);
    }

    public function test_tool_step_executes_registered_code_tool(): void
    {
        app(HandlerRegistry::class)->register('orders.lookup', new class implements CodeToolHandler {
            public function handle(ToolExecutionContext $context): ToolResult
            {
                return ToolResult::success([
                    'id' => $context->arguments['id'] ?? null,
                    'status' => 'ready',
                ]);
            }
        });

        app(ToolFactory::class)->register(new ToolDefinition(
            name: 'orders.lookup',
            description: 'Lookup order',
            driver: 'code',
            configuration: ['handler' => 'orders.lookup'],
        ));

        $result = app(WorkflowRunner::class)->run(
            new WorkflowDefinition(
                slug: 'order-flow',
                name: 'Order Flow',
                steps: [
                    [
                        'id' => 'lookup',
                        'type' => 'tool',
                        'tool' => 'orders.lookup',
                        'arguments' => ['id' => '{input.id}'],
                        'save_as' => 'order',
                    ],
                    ['id' => 'done', 'type' => 'complete', 'output' => ['order' => '{order}']],
                ],
            ),
            ['id' => 42],
        );

        $this->assertTrue($result->success);
        $order = json_decode((string) $result->output['order'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('42', (string) $order['id']);
        $this->assertSame('ready', $order['status']);
    }
}
