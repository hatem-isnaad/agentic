<?php

namespace Agentic\Conversation;

use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\ExecutionStatus;
use Agentic\Models\ToolApproval;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolApprovalService;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolFactory;
use Agentic\Widget\Reply\WidgetApprovalCard;

final class HandoffOfferService
{
    public const Tool = 'handoff';

    public function __construct(
        private ToolFactory $tools,
        private ToolRegistry $registry,
        private ToolApprovalService $approvals,
        private ExecutionRepository $executions,
    ) {}

    public function enabled(): bool
    {
        return filter_var(config('agentic.widget.handoff.enabled', true), FILTER_VALIDATE_BOOL);
    }

    public function ensureRegistered(): void
    {
        if ($this->tools->ensureRegistered(self::Tool)) {
            return;
        }

        $this->tools->register($this->definition());
    }

    /**
     * @param  list<ToolContract>  $tools
     * @return list<ToolContract>
     */
    public function attachToTools(array $tools, AgentExecutionContext $context): array
    {
        if (! $this->enabled() || ! $this->isWidget($context)) {
            return $tools;
        }

        $this->ensureRegistered();

        foreach ($tools as $tool) {
            if ($tool->definition()->name === self::Tool) {
                return $tools;
            }
        }

        $tools[] = $this->registry->resolve(self::Tool);

        return $tools;
    }

    /**
     * @param  list<ToolContract>  $tools
     */
    public function withInstruction(string $instructions, array $tools): string
    {
        $hasHandoff = false;
        foreach ($tools as $tool) {
            if ($tool->definition()->name === self::Tool) {
                $hasHandoff = true;
                break;
            }
        }

        if (! $hasHandoff) {
            return $instructions;
        }

        $hint = 'When tools fail, data is missing, you lack permission, or you cannot give a useful answer, call the handoff tool so the user can approve talking to a person. Do not claim a person already joined. Do not call handoff for greetings or when you already answered well.';

        if (str_contains($instructions, 'call the handoff tool')) {
            return $instructions;
        }

        return trim($instructions) === '' ? $hint : trim($instructions)."\n\n".$hint;
    }

    public function userConfirmed(string $conversationId, string $message): bool
    {
        if (! $this->enabled() || $this->pending($conversationId) === null) {
            return false;
        }

        $text = mb_strtolower(trim($message));
        if ($text === '' || mb_strlen($text) > 80) {
            return false;
        }

        return (bool) preg_match(
            '/^(yes|yep|yeah|ok|okay|sure|please|do it|connect( me)?|talk to (a )?person|i agree|go ahead|نعم|ايوه|أيوه|موافق|تمام|أبغى|ابي|حولني|اتفضل|تفضل|وصلني|وصّلني)([\s!.،,]*(please|person|human|موظف|شخص)?)?$/u',
            $text,
        );
    }

    public function closePending(string $conversationId, string $status = 'approved'): void
    {
        $pending = $this->pending($conversationId);
        if ($pending === null) {
            return;
        }

        if ($status === 'rejected') {
            $this->approvals->reject($pending, 'user');

            return;
        }

        $this->approvals->approve($pending, 'user');
    }

    /**
     * @param  list<array{id?: string, tool?: string}>  $pending
     * @return list<array<string, mixed>>|null
     */
    public function maybeOfferBlocks(
        string $conversationId,
        string $agent,
        ?string $executionId,
        array $pending,
        bool $succeeded,
    ): ?array {
        if (! $this->enabled() || $pending !== [] || $this->pending($conversationId) !== null) {
            return null;
        }

        if ($succeeded && ! $this->executionLooksStuck($executionId)) {
            return null;
        }

        $this->ensureRegistered();
        $record = $this->approvals->createPending(
            $this->registry->resolve(self::Tool),
            ['reason' => 'Could not finish this request.'],
            executionUuid: $executionId,
            conversationUuid: $conversationId,
            agent: $agent,
            metadata: ['kind' => 'handoff', 'reason' => 'Could not finish this request.'],
        );

        return WidgetApprovalCard::blocks([
            [
                'id' => $record->uuid,
                'tool' => self::Tool,
                'reason' => 'I could not get a useful result. A teammate can take the chat and help you from here.',
            ],
        ]);
    }

    public function pending(string $conversationId): ?ToolApproval
    {
        return ToolApproval::query()
            ->where('conversation_uuid', $conversationId)
            ->where('tool', self::Tool)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            name: self::Tool,
            description: 'Offer to connect the user to a human teammate when you cannot get a useful result. The user must approve before the chat converts.',
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'reason' => [
                        'type' => 'string',
                        'description' => 'Short reason a person is needed.',
                    ],
                ],
            ],
            driver: 'code',
            configuration: [
                'handler' => self::Tool,
                'requires_approval' => true,
            ],
            permissions: [self::Tool],
        );
    }

    private function isWidget(AgentExecutionContext $context): bool
    {
        $channel = $context->runtime()->get('channel') ?? ($context->metadata['channel'] ?? null);

        return $channel === 'widget';
    }

    private function executionLooksStuck(?string $executionId): bool
    {
        if (! is_string($executionId) || $executionId === '') {
            return false;
        }

        $execution = $this->executions->find($executionId);
        if ($execution === null) {
            return false;
        }

        foreach ($execution->steps as $step) {
            if ($step->type === 'tool_call' && $step->status === ExecutionStatus::Failed) {
                return true;
            }
        }

        return false;
    }
}
