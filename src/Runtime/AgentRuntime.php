<?php

namespace Agentic\Runtime;

use Agentic\Agent\AgentDefinition;
use Agentic\Channels\ChannelAgentRunner;
use Agentic\Context\ContextBuilder;
use Agentic\Context\ContextManager;
use Agentic\Context\ContextPolicyResolver;
use Agentic\Conversation\ConversationHistoryForLlm;
use Agentic\Conversation\ConversationManager;
use Agentic\Conversation\HandoffOfferService;
use Agentic\Exceptions\AgentExecutionFailedException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\AgentExecutionResult;
use Agentic\Execution\ExecutionManager;
use Agentic\Execution\ExecutionStatus;
use Agentic\Integrations\LaravelAi\LaravelAiSdkAdapter;
use Agentic\Persistence\ToolVersionResolver;
use Agentic\Skill\SkillRouter;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolApprovalService;
use Agentic\Widget\Reply\WidgetApprovalCard;
use Throwable;

/**
 * Orchestrates Agent execution.
 *
 * Runtime coordinates context, conversation, tools, permissions, and execution
 * tracking, then delegates AI execution to the Laravel AI SDK adapter.
 * It does not query Eloquent.
 */
final class AgentRuntime implements ChannelAgentRunner
{
    public function __construct(
        private ContextBuilder $contextBuilder,
        private ContextManager $contexts,
        private ConversationManager $conversations,
        private ToolRegistry $tools,
        private LaravelAiSdkAdapter $ai,
        private ExecutionManager $executions,
        private SkillRouter $skillRouter,
        private ToolVersionResolver $toolVersions,
        private ConversationHistoryForLlm $conversationHistory,
        private ToolApprovalService $approvals,
        private HandoffOfferService $handoffOffers,
    ) {}

    public function run(AgentDefinition $agent, AgentExecutionContext $context): AgentExecutionResult
    {
        $conversation = $context->conversation;

        if ($conversation === null && $context->conversationId !== null) {
            $conversation = $this->conversations->continue($context->conversationId);
        }

        $runtime = $this->contexts->build(
            seed: $context->runtime()->all(),
            agent: $agent,
            conversation: $conversation,
        );
        $runtime = ContextPolicyResolver::applyIfMissing($runtime);

        $policy = $runtime->get('context_policy');
        $policy = is_array($policy) ? $policy : [];

        $messages = $context->messages;
        if (
            $messages === []
            && ($policy['enabled'] ?? false) === true
            && (int) ($policy['max_history_messages'] ?? 0) > 0
        ) {
            $conversationUuid = $conversation?->id ?? $context->conversationId;
            if (is_string($conversationUuid) && $conversationUuid !== '') {
                $messages = $this->conversationHistory->priorMessages(
                    $conversationUuid,
                    (int) $policy['max_history_messages'],
                );
            }
        }

        $context = new AgentExecutionContext(
            message: $context->message,
            metadata: $context->metadata,
            variables: $context->variables,
            messages: $messages,
            runtime: $runtime,
            conversation: $conversation,
            conversationId: $conversation?->id ?? $context->conversationId,
            executionId: $context->executionId,
            attachments: $context->attachments,
        );

        $execution = $this->executions->start(
            agent: $agent->identifier(),
            input: [
                'message' => $context->message,
                'metadata' => $context->metadata,
                'variables' => $context->variables,
            ],
            metadata: [
                'model' => $agent->model,
                'provider' => $agent->provider,
            ],
            conversationId: $conversation?->id,
        );

        try {
            $context = new AgentExecutionContext(
                message: $context->message,
                metadata: $context->metadata,
                variables: $context->variables,
                messages: $context->messages,
                runtime: $context->runtime,
                conversation: $context->conversation,
                conversationId: $context->conversationId,
                executionId: $execution->id,
                attachments: $context->attachments,
            );

            $this->executions->addStep($execution, 'agent_start', [
                'agent' => $agent->identifier(),
                'conversation_id' => $conversation?->id,
            ]);

            $skillSelection = null;

            $policy = $context->runtime()->get('context_policy');
            $policy = is_array($policy) ? $policy : [];

            if ((bool) config('agentic.skill_routing.enabled', true)) {
                $skillLimit = (int) ($policy['skill_routing_limit'] ?? config('agentic.skill_routing.limit', 3));
                $fallbackLimit = null;
                if (array_key_exists('skills_fallback_limit', $policy)) {
                    $fallbackLimit = (int) $policy['skills_fallback_limit'];
                } elseif (filter_var(config('agentic.context.lean_enabled', true), FILTER_VALIDATE_BOOL)) {
                    $fallbackLimit = (int) config('agentic.skill_routing.fallback_limit', 4);
                }

                $skillSelection = $this->skillRouter->select(
                    $agent,
                    $context->message,
                    $skillLimit,
                    $fallbackLimit,
                );
            }

            $built = $this->contextBuilder->build(
                $agent,
                $context->message,
                $context->runtime(),
                $skillSelection?->skills,
            );
            $selectedTools = $this->resolveTools($built['tools']);

            $maxTools = (int) ($policy['max_tools'] ?? 0);
            if ($maxTools > 0 && count($selectedTools) > $maxTools) {
                $selectedTools = array_slice($selectedTools, 0, $maxTools);
            }
            $selectedTools = $this->handoffOffers->attachToTools($selectedTools, $context);
            $built['instructions'] = $this->handoffOffers->withInstruction(
                (string) ($built['instructions'] ?? ''),
                $selectedTools,
            );
            $pinnedVersions = $this->pinToolVersions($selectedTools);

            $context = new AgentExecutionContext(
                message: $context->message,
                metadata: $context->metadata,
                variables: $context->variables,
                messages: $context->messages,
                runtime: $context->runtime->with('tool_version_ids', $pinnedVersions),
                conversation: $context->conversation,
                conversationId: $context->conversationId,
                executionId: $execution->id,
                attachments: $context->attachments,
            );

            $this->executions->addStep($execution, 'skill_selection', output: [
                'skills' => $skillSelection?->skills ?? $agent->skills,
                'matches' => $skillSelection?->matches ?? [],
            ]);

            $this->executions->addStep($execution, 'tool_version_pin', output: [
                'versions' => $pinnedVersions,
            ]);

            $this->executions->addStep($execution, 'llm_request', [
                'message' => $context->message,
                'tools' => array_map(fn (ToolContract $tool) => $tool->definition()->name, $selectedTools),
            ]);

            $response = $this->ai->prompt($agent, $built, $selectedTools, $context);

            if ($conversation !== null && is_string($response->conversationId) && $response->conversationId !== '') {
                $conversation = $this->conversations->bindSdkConversation(
                    $conversation,
                    $response->conversationId,
                );
            }

            $pending = $this->persistPendingApprovals(
                $response,
                $agent->identifier(),
                $execution->id,
                $conversation?->id,
            );

            $output = [
                'text' => $response->text,
                'invocation_id' => $response->invocationId,
                'conversation_id' => $conversation?->id,
                'sdk_conversation_id' => $response->conversationId,
                'usage' => $this->usageArray($response->usage),
                'execution_id' => $execution->id,
                'pending_approvals' => $pending,
            ];

            if ($pending !== []) {
                $output['format'] = 'blocks';
                $output['blocks'] = WidgetApprovalCard::blocks($pending, (string) $response->text);
            }

            $this->executions->addStep($execution, 'final_response', output: [
                'text' => $response->text,
            ]);

            $execution->conversationId = $conversation?->id;
            $this->executions->complete($execution, $output);

            return AgentExecutionResult::success($output);
        } catch (Throwable $exception) {
            $message = (new AgentExecutionFailedException(
                $agent->identifier(),
                $exception->getMessage(),
                $exception,
            ))->getMessage();

            $this->executions->addStep(
                $execution,
                'error',
                input: [],
                output: ['error' => $message],
                status: ExecutionStatus::Failed,
            );
            $this->executions->fail($execution, $message);

            return AgentExecutionResult::failure($message);
        }
    }

    /**
     * @param  list<string>  $names
     * @return list<ToolContract>
     */
    private function resolveTools(array $names): array
    {
        $resolved = [];

        foreach ($names as $name) {
            if ($this->tools->has($name)) {
                $resolved[] = $this->tools->resolve($name);
            }
        }

        return $resolved;
    }

    /**
     * @param  list<ToolContract>  $tools
     * @return array<string, int>
     */
    private function pinToolVersions(array $tools): array
    {
        $pinned = [];

        foreach ($tools as $tool) {
            $id = $this->toolVersions->resolveId($tool->definition());

            if ($id !== null) {
                $pinned[$tool->definition()->name] = $id;
            }
        }

        return $pinned;
    }

    /**
     * @return list<array{id: string, tool: string, arguments: array<string, mixed>, reason: string|null}>
     */
    private function persistPendingApprovals(mixed $response, string $agent, string $executionId, ?string $conversationId): array
    {
        $pending = $response->pendingApprovals ?? null;
        if (! is_iterable($pending)) {
            return [];
        }

        $rows = [];

        foreach ($pending as $item) {
            $toolName = is_object($item) ? (string) ($item->tool ?? '') : (string) ($item['tool'] ?? '');
            $arguments = [];
            if (is_object($item) && is_array($item->arguments ?? null)) {
                $arguments = $item->arguments;
            } elseif (is_array($item) && is_array($item['arguments'] ?? null)) {
                $arguments = $item['arguments'];
            }
            $reason = is_object($item) ? ($item->reason ?? null) : (is_array($item) ? ($item['reason'] ?? null) : null);

            if ($toolName === '' || ! $this->tools->has($toolName)) {
                continue;
            }

            $record = $this->approvals->createPending(
                $this->tools->resolve($toolName),
                $arguments,
                executionUuid: $executionId,
                conversationUuid: $conversationId,
                agent: $agent,
                metadata: [
                    'sdk_approval_id' => is_object($item) ? ($item->id ?? null) : ($item['id'] ?? null),
                    'reason' => $reason,
                ],
            );

            $rows[] = [
                'id' => $record->uuid,
                'tool' => $record->tool,
                'arguments' => is_array($record->arguments) ? $record->arguments : [],
                'reason' => is_string($reason) ? $reason : null,
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function usageArray(mixed $usage): array
    {
        if (is_object($usage) && method_exists($usage, 'toArray')) {
            return $usage->toArray();
        }

        return is_array($usage) ? $usage : [];
    }
}
