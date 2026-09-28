<?php

namespace Agentic\Widget\Reply;

use Agentic\Models\ToolApproval;

/**
 * Structured Approve / Reject card for the official widget.
 *
 * @phpstan-type PendingApproval array{id: string, tool: string, arguments?: array<string, mixed>, reason?: string|null}
 */
final class WidgetApprovalCard
{
    /**
     * @param  list<PendingApproval>  $approvals
     * @return list<array<string, mixed>>
     */
    public static function blocks(array $approvals, string $intro = ''): array
    {
        $blocks = [];
        $lead = trim($intro);
        $introIsTitle = $lead !== '' && ! self::isGenericCopy($lead) && mb_strlen($lead) <= 80;
        if ($lead !== '' && ! $introIsTitle && ! self::isGenericCopy($lead)) {
            $blocks[] = ['type' => 'text', 'text' => $lead];
        }

        foreach ($approvals as $approval) {
            $id = (string) ($approval['id'] ?? '');
            $tool = (string) ($approval['tool'] ?? 'tool');
            if ($id === '') {
                continue;
            }

            $isHandoff = $tool === 'handoff';
            $title = $isHandoff
                ? 'Talk to a person?'
                : ($introIsTitle ? $lead : 'Approve this action?');

            $blocks[] = [
                'type' => 'card',
                'variant' => $isHandoff ? 'handoff' : 'approval',
                'title' => $title,
                'body' => 'The agent needs your approval before running this action.',
                'buttons' => $isHandoff
                    ? [
                        ['label' => 'Approve', 'action' => 'approve', 'style' => 'primary', 'payload' => ['id' => $id]],
                        ['label' => 'Reject', 'action' => 'reject', 'style' => 'default', 'payload' => ['id' => $id]],
                    ]
                    : [
                        ['label' => 'Approve', 'action' => 'approve', 'style' => 'primary', 'payload' => ['id' => $id]],
                        ['label' => 'Reject', 'action' => 'reject', 'style' => 'default', 'payload' => ['id' => $id]],
                    ],
            ];
        }

        return $blocks;
    }

    /**
     * Upgrade stored block JSON from older widget versions (separate card + actions rows).
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function normalizeBlocks(array $blocks): array
    {
        $hasModern = false;
        foreach ($blocks as $block) {
            $variant = (string) ($block['variant'] ?? '');
            if ($variant === 'handoff' || $variant === 'approval') {
                $hasModern = true;
                break;
            }
        }

        if (! $hasModern) {
            $buttons = [];
            $card = null;
            foreach ($blocks as $block) {
                $type = (string) ($block['type'] ?? '');
                if ($type === 'actions') {
                    $buttons = is_array($block['buttons'] ?? null) ? $block['buttons'] : [];
                } elseif ($type === 'card') {
                    $card = $block;
                }
            }

            if ($buttons !== []) {
                $approvalId = self::approvalIdFromButtons($buttons);
                if ($approvalId !== null) {
                    $title = is_array($card) ? (string) ($card['title'] ?? '') : '';
                    $isHandoff = str_contains(mb_strtolower($title), 'person');
                    $blocks = self::blocks([
                        [
                            'id' => $approvalId,
                            'tool' => $isHandoff ? 'handoff' : $title,
                            'reason' => is_array($card) ? (string) ($card['body'] ?? '') : '',
                        ],
                    ]);
                }
            }
        }

        return self::withoutResolvedApprovalCards($blocks);
    }

    /**
     * Drop approval / handoff cards once the visitor already approved or rejected.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function withoutResolvedApprovalCards(array $blocks): array
    {
        $filtered = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? '');

            if ($type === 'actions') {
                $id = self::approvalIdFromButtons(is_array($block['buttons'] ?? null) ? $block['buttons'] : []);
                if ($id !== null && ! self::isApprovalPending($id)) {
                    continue;
                }

                $filtered[] = $block;

                continue;
            }

            if ($type !== 'card') {
                $filtered[] = $block;

                continue;
            }

            $variant = (string) ($block['variant'] ?? '');
            if ($variant !== 'handoff' && $variant !== 'approval') {
                $filtered[] = $block;

                continue;
            }

            $id = self::approvalIdFromButtons(is_array($block['buttons'] ?? null) ? $block['buttons'] : []);
            if ($id !== null && ! self::isApprovalPending($id)) {
                continue;
            }

            $filtered[] = $block;
        }

        return $filtered;
    }

    /**
     * @param  list<array<string, mixed>>  $buttons
     */
    private static function approvalIdFromButtons(array $buttons): ?string
    {
        foreach ($buttons as $button) {
            if (! is_array($button)) {
                continue;
            }
            $payload = is_array($button['payload'] ?? null) ? $button['payload'] : [];
            $id = $payload['id'] ?? null;
            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        return null;
    }

    private static function isApprovalPending(string $approvalId): bool
    {
        try {
            $status = ToolApproval::query()->where('uuid', $approvalId)->value('status');
        } catch (\Throwable) {
            return true;
        }

        if ($status === null) {
            return true;
        }

        return $status === 'pending';
    }

    private static function isGenericCopy(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        if ($normalized === '') {
            return true;
        }

        return str_contains($normalized, 'needs your approval')
            || $normalized === 'connect this chat to a person?'
            || $normalized === 'could not finish this request.';
    }
}
