<?php

namespace Agentic\Tests\Unit;

use Agentic\Models\ToolApproval;
use Agentic\Tests\TestCase;
use Agentic\Widget\Reply\HtmlReplyRenderer;
use Agentic\Widget\Reply\WidgetApprovalCard;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WidgetApprovalCardTest extends TestCase
{
    use RefreshDatabase;
    public function test_blocks_include_approve_and_reject_buttons(): void
    {
        $blocks = WidgetApprovalCard::blocks([
            ['id' => '11111111-1111-1111-1111-111111111111', 'tool' => 'orders.update', 'arguments' => ['id' => 4]],
        ], 'Confirm this change?');

        $html = (new HtmlReplyRenderer)->renderBlocks($blocks);

        $this->assertStringContainsString('data-action="approve"', $html);
        $this->assertStringContainsString('data-action="reject"', $html);
        $this->assertStringContainsString('Confirm this change?', $html);
        $this->assertStringContainsString('The agent needs your approval before running this action.', $html);
        $this->assertStringContainsString('agentic-card-approval', $html);
        $this->assertStringContainsString('Approve', $html);
        $this->assertStringContainsString('Reject', $html);
    }

    public function test_handoff_card_asks_in_chat(): void
    {
        $blocks = WidgetApprovalCard::blocks([
            ['id' => '22222222-2222-2222-2222-222222222222', 'tool' => 'handoff', 'reason' => 'Order lookup failed.'],
        ]);

        $html = (new HtmlReplyRenderer)->renderBlocks($blocks);

        $this->assertStringContainsString('agentic-card-handoff', $html);
        $this->assertStringContainsString('Talk to a person?', $html);
        $this->assertStringContainsString('The agent needs your approval before running this action.', $html);
        $this->assertStringContainsString('Approve', $html);
        $this->assertStringContainsString('Reject', $html);
        $this->assertStringContainsString('data-action="approve"', $html);
        $this->assertStringContainsString('agentic-card-icon', $html);
    }

    public function test_normalize_blocks_upgrades_legacy_handoff_rows(): void
    {
        $blocks = WidgetApprovalCard::normalizeBlocks([
            ['type' => 'text', 'text' => 'This action needs your approval before it can run.'],
            ['type' => 'card', 'title' => 'Talk to a person', 'body' => 'Connect this chat to a person?'],
            [
                'type' => 'actions',
                'buttons' => [
                    ['label' => 'Yes', 'action' => 'approve', 'style' => 'primary', 'payload' => ['id' => '33333333-3333-3333-3333-333333333333']],
                    ['label' => 'No', 'action' => 'reject', 'style' => 'default', 'payload' => ['id' => '33333333-3333-3333-3333-333333333333']],
                ],
            ],
        ]);

        $html = (new HtmlReplyRenderer)->renderBlocks($blocks);

        $this->assertStringContainsString('agentic-card-handoff', $html);
        $this->assertStringNotContainsString('This action needs your approval before it can run.', $html);
    }

    public function test_normalize_blocks_hides_resolved_approval_cards(): void
    {
        $id = '44444444-4444-4444-4444-444444444444';
        ToolApproval::query()->create([
            'uuid' => $id,
            'execution_uuid' => 'exec-1',
            'conversation_uuid' => 'conv-1',
            'agent' => 'default',
            'tool' => 'handoff',
            'arguments' => [],
            'status' => 'approved',
        ]);

        $blocks = WidgetApprovalCard::normalizeBlocks(
            WidgetApprovalCard::blocks([['id' => $id, 'tool' => 'handoff']]),
        );

        $this->assertSame([], $blocks);
    }
}
