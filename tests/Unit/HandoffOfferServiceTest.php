<?php

namespace Agentic\Tests\Unit;

use Agentic\Conversation\HandoffOfferService;
use Agentic\Tests\TestCase;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class HandoffOfferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_yes_confirms_only_when_an_offer_is_pending(): void
    {
        $offers = app(HandoffOfferService::class);
        $offers->ensureRegistered();

        $this->assertFalse($offers->userConfirmed('missing', 'yes'));

        $approval = app(ToolApprovalService::class)->createPending(
            app(ToolRegistry::class)->resolve('handoff'),
            ['reason' => 'stuck'],
            conversationUuid: 'conv-1',
            agent: 'support',
        );

        $this->assertTrue($offers->userConfirmed('conv-1', 'yes'));
        $this->assertTrue($offers->userConfirmed('conv-1', 'موافق'));
        $this->assertFalse($offers->userConfirmed('conv-1', 'show me order 1004 please'));
        $this->assertSame('pending', $approval->fresh()?->status);
    }
}
