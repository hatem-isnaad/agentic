<?php

namespace Agentic\Tests\Feature;

use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Conversation\ConversationHandoffService;
use Agentic\Conversation\HandoffOfferService;
use Agentic\Enums\Status;
use Agentic\Jobs\ProcessWidgetMessageJob;
use Agentic\Models\Agent;
use Agentic\Models\ConversationMessage;
use Agentic\Tests\TestCase;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tool\ToolApprovalService;
use Agentic\Widget\Services\WidgetMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

final class WidgetHandoffApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.widget.enabled', true);
        $app['config']->set('agentic.conversation.driver', 'eloquent');
        $app['config']->set('agentic.widget.embed.require_token', false);
        $app['config']->set('agentic.widget.async_replies', true);
    }

    public function test_widget_handoff_pauses_the_agent(): void
    {
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $headers = ['X-Agentic-Guest-Id' => 'guest-handoff'];

        $started = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'I need a person',
        ], $headers)->assertOk();

        $conversationId = $started->json('data.conversation_id');
        Queue::assertPushed(ProcessWidgetMessageJob::class);

        $this->postJson('/api/agentic/widget/conversations/'.$conversationId.'/handoff', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.handoff', true);

        Queue::fake();

        $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'conversation_id' => $conversationId,
            'message' => 'Still waiting',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.handoff', true);

        Queue::assertNothingPushed();
    }

    public function test_queued_job_skips_agent_when_thread_is_human(): void
    {
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $headers = ['X-Agentic-Guest-Id' => 'guest-handoff-job'];
        $started = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'Need help',
        ], $headers)->assertOk();

        $conversationId = (string) $started->json('data.conversation_id');
        $job = new ProcessWidgetMessageJob('support', $conversationId, 'Need help');

        app(ConversationHandoffService::class)->request($conversationId, 'user approved', 'widget');

        $before = ConversationMessage::query()
            ->whereHas('conversation', fn ($query) => $query->where('uuid', $conversationId))
            ->where('role', 'assistant')
            ->count();

        $job->handle(app(WidgetMessageService::class), app(ConversationHandoffService::class));

        $after = ConversationMessage::query()
            ->whereHas('conversation', fn ($query) => $query->where('uuid', $conversationId))
            ->where('role', 'assistant')
            ->count();

        $this->assertSame($before, $after);
    }

    public function test_user_yes_after_offer_converts_without_agent_job(): void
    {
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $headers = ['X-Agentic-Guest-Id' => 'guest-handoff-yes'];

        $started = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'Where is my order?',
        ], $headers)->assertOk();

        $conversationId = (string) $started->json('data.conversation_id');
        $offers = app(HandoffOfferService::class);
        $offers->ensureRegistered();
        app(ToolApprovalService::class)->createPending(
            app(ToolRegistry::class)->resolve('handoff'),
            ['reason' => 'lookup failed'],
            conversationUuid: $conversationId,
            agent: 'support',
        );

        Queue::fake();

        $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'conversation_id' => $conversationId,
            'message' => 'yes',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.handoff', true);

        Queue::assertNothingPushed();
    }

    public function test_approving_handoff_offer_converts_the_thread(): void
    {
        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $headers = ['X-Agentic-Guest-Id' => 'guest-handoff-approve'];
        $started = $this->postJson('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'Need help',
        ], $headers)->assertOk();

        $conversationId = (string) $started->json('data.conversation_id');
        $offers = app(HandoffOfferService::class);
        $offers->ensureRegistered();
        $approval = app(ToolApprovalService::class)->createPending(
            app(ToolRegistry::class)->resolve('handoff'),
            ['reason' => 'lookup failed'],
            conversationUuid: $conversationId,
            agent: 'support',
        );

        $this->postJson('/api/agentic/widget/approvals/'.$approval->uuid.'/approve', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.tool', 'handoff')
            ->assertJsonPath('data.handoff', true)
            ->assertJsonPath('data.execution.success', true);

        $conversation = app(ConversationRepository::class)->find($conversationId);
        $this->assertNotNull($conversation);
        $this->assertSame(ConversationHandoffService::Requested, app(ConversationHandoffService::class)->status($conversation));
    }
}
