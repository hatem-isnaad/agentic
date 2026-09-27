<?php

namespace Agentic\Tool;

use Agentic\Events\PermissionChecked;
use Agentic\Events\PermissionDenied;
use Agentic\Events\ToolExecutionCompleted;
use Agentic\Events\ToolExecutionFailed;
use Agentic\Events\ToolExecutionStarted;
use Agentic\Permission\PermissionResolver;
use Agentic\Execution\ExecutionManager;
use Throwable;
use Agentic\Tool\Contracts\ToolContract;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Executes a tool after permission resolution.
 *
 * Denied tools never reach a ToolDriver.
 */
final class ToolExecutor
{
    public function __construct(
        private PermissionResolver $permissions,
        private ?Dispatcher $events = null,
        private ?ExecutionManager $executions = null,
    ) {}

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        $agent = $context->runtime()->agent();
        $decision = $this->permissions->decide($tool, $agent, $context->runtime());

        $this->events?->dispatch(new PermissionChecked(
            tool: $tool->definition()->name,
            allowed: $decision->allowed(),
            agent: $agent?->identifier(),
        ));

        if (! $decision->allowed()) {
            $message = $this->permissions->denialMessage($tool);

            $this->record($tool, $context, false, null, 0, $message);

            $this->events?->dispatch(new PermissionDenied(
                tool: $tool->definition()->name,
                message: $message,
                agent: $agent?->identifier(),
            ));

            return ToolResult::failure($message);
        }

        $this->events?->dispatch(new ToolExecutionStarted(
            tool: $tool->definition()->name,
            arguments: $context->arguments,
            agent: $agent?->identifier(),
        ));

        $started = microtime(true);

        try {
            $result = $tool->execute($context);
        } catch (Throwable $exception) {
            $result = ToolResult::failure($exception->getMessage());
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $this->record($tool, $context, true, $result, $durationMs);

        if ($result->success) {
            $this->events?->dispatch(new ToolExecutionCompleted(
                tool: $tool->definition()->name,
                result: $result->data,
                agent: $agent?->identifier(),
            ));
        } else {
            $this->events?->dispatch(new ToolExecutionFailed(
                tool: $tool->definition()->name,
                error: (string) $result->error,
                agent: $agent?->identifier(),
            ));
        }


        return $result;
    }

    private function record(
        ToolContract $tool,
        ToolExecutionContext $context,
        bool $permissionAllowed,
        ?ToolResult $result,
        int $durationMs,
        ?string $error = null,
    ): void {
        if ($this->executions === null || $context->execution?->executionId === null) {
            return;
        }

        $execution = $this->executions->start(
            agent: $context->runtime()->agent()?->identifier() ?? 'unknown',
            input: [],
            metadata: ['internal_tool_step' => true],
            conversationId: $context->execution->conversationId,
        );

        $execution->id = $context->execution->executionId;

        $this->executions->addStep(
            $execution,
            'tool_call',
            input: $this->redact($context->arguments),
            output: $result?->success ? $this->redact($result->data) : ['error' => $error ?? $result?->error],
            status: $result?->success === false ? \Agentic\Execution\ExecutionStatus::Failed : \Agentic\Execution\ExecutionStatus::Completed,
            metadata: ['tool' => $tool->definition()->name],
            toolId: $tool->definition()->id,
            toolVersionId: $tool->definition()->version,
            permissionAllowed: $permissionAllowed,
            durationMs: $durationMs,
        );
    }

    private function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $sensitive = ['authorization', 'token', 'password', 'secret', 'api_key', 'access_token', 'refresh_token'];

        foreach ($value as $key => $item) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $value[$key] = '[REDACTED]';
            } elseif (is_array($item)) {
                $value[$key] = $this->redact($item);
            }
        }

        return $value;
    }
}
