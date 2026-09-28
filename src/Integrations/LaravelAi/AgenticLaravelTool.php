<?php

namespace Agentic\Integrations\LaravelAi;

use Agentic\Context\LlmInputCompactor;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\ToolApprovalService;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolExecutor;
use Agentic\Tool\ToolResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class AgenticLaravelTool implements Approvable, Tool
{
    use InteractsWithApprovals;

    public function __construct(
        private ToolContract $tool,
        private ToolExecutor $executor,
        private ToolApprovalService $approvals,
        private ?ToolExecutionContext $baseContext = null,
    ) {}

    public function name(): string
    {
        return $this->tool->definition()->name;
    }

    public function description(): Stringable|string
    {
        return LlmInputCompactor::fromConfig()->toolDescription($this->tool->definition()->description);
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        if ($this->tool->definition()->name === 'handoff') {
            $channel = $this->baseContext?->runtime()->get('channel')
                ?? ($this->baseContext?->metadata['channel'] ?? null);
            if ($channel === 'widget' && filter_var(config('agentic.widget.handoff.enabled', true), FILTER_VALIDATE_BOOL)) {
                return Approval::required('Connect this chat to a person?');
            }

            return false;
        }

        return $this->approvals->decision($this->tool);
    }

    public function handle(Request $request): Stringable|string
    {
        $context = new ToolExecutionContext(
            arguments: $request->all(),
            metadata: $this->baseContext?->metadata ?? [],
            execution: $this->baseContext?->execution,
            runtime: $this->baseContext?->runtime(),
        );

        $result = $this->executor->execute($this->tool, $context);

        return $this->stringify($result);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return JsonSchemaMapper::map($schema, $this->tool->definition()->inputSchema);
    }

    private function stringify(ToolResult $result): string
    {
        return LlmInputCompactor::fromConfig()->toolResult(
            $result->success,
            $result->data,
            $result->error,
        );
    }
}
