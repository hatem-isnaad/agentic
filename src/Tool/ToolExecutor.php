<?php

namespace Agentic\Tool;

use Agentic\Events\PermissionChecked;
use Agentic\Events\PermissionDenied;
use Agentic\Events\ToolExecutionCompleted;
use Agentic\Events\ToolExecutionFailed;
use Agentic\Events\ToolExecutionStarted;
use Agentic\Permission\PermissionResolver;
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

        $result = $tool->execute($context);

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
}
