<?php

namespace Agentic\Tests\Unit;

use Agentic\Tool\Contracts\CodeToolHandler;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolFactory;
use Agentic\Tool\ToolResult;
use Agentic\Tests\TestCase;
use Agentic\Tool\ToolApprovalService;
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

    public function test_approval_step_returns_pending_until_resumed_with_approved_id(): void
    {
        $workflow = new WorkflowDefinition(
            slug: 'charge',
            name: 'Charge',
            steps: [
                [
                    'id' => 'confirm',
                    'type' => 'approval',
                    'title' => 'Approve charge',
                    'payload' => ['amount' => '{input.amount}'],
                ],
                ['id' => 'done', 'type' => 'complete', 'output' => ['ok' => true]],
            ],
        );

        $pending = app(WorkflowRunner::class)->run($workflow, ['amount' => 99]);

        $this->assertTrue($pending->pending);
        $this->assertNotEmpty($pending->approvalId);

        app(ToolApprovalService::class)->approve(
            app(ToolApprovalService::class)->find($pending->approvalId),
        );

        $completed = app(WorkflowRunner::class)->run($workflow, [
            'amount' => 99,
            '_resume_approval_id' => $pending->approvalId,
        ]);

        $this->assertTrue($completed->success);
        $this->assertSame(['ok' => true], $completed->output);
    }

    public function test_parallel_step_runs_branches_and_merges_results(): void
    {
        $result = app(WorkflowRunner::class)->run(
            new WorkflowDefinition(
                slug: 'parallel-greet',
                name: 'Parallel Greet',
                steps: [
                    [
                        'id' => 'fanout',
                        'type' => 'parallel',
                        'branches' => [
                            [
                                'save_as' => 'left',
                                'step' => ['type' => 'set', 'variables' => ['label' => 'L']],
                            ],
                            [
                                'save_as' => 'right',
                                'step' => ['type' => 'set', 'variables' => ['label' => 'R']],
                            ],
                        ],
                    ],
                    [
                        'id' => 'done',
                        'type' => 'complete',
                        'output' => [
                            'left' => '{left.label}',
                            'right' => '{right.label}',
                        ],
                    ],
                ],
            ),
            [],
        );

        $this->assertTrue($result->success);
        $this->assertSame('L', $result->output['left']);
        $this->assertSame('R', $result->output['right']);
    }
}
