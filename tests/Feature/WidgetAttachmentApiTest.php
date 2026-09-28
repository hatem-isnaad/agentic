<?php

namespace Agentic\Tests\Feature;

use Agentic\Conversation\ConversationHandoffService;
use Agentic\Enums\Status;
use Agentic\Jobs\ProcessWidgetMessageJob;
use Agentic\Models\Agent;
use Agentic\Models\ConversationMessage;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

final class WidgetAttachmentApiTest extends TestCase
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

    public function test_widget_rejects_attachment_before_staff_takes_chat(): void
    {
        Storage::fake('local');
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $this->post('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'See this',
            'files' => [UploadedFile::fake()->image('receipt.jpg')],
        ], [
            'X-Agentic-Guest-Id' => 'guest-attach',
        ])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_uploaded_image_is_rendered_in_conversation_when_staff_chat_active(): void
    {
        Storage::fake('local');
        Queue::fake();

        Agent::query()->create([
            'name' => 'Support',
            'slug' => 'support',
            'status' => Status::Published,
            'instructions' => 'Help',
        ]);

        $headers = ['X-Agentic-Guest-Id' => 'guest-attach-render'];
        $started = $this->post('/api/agentic/widget/messages', [
            'agent' => 'support',
            'message' => 'Hi',
        ], $headers)->assertOk();

        $conversationId = (string) $started->json('data.conversation_id');
        app(ConversationHandoffService::class)->take($conversationId, 'staff');

        $jobsBeforeUpload = Queue::pushed(ProcessWidgetMessageJob::class)->count();

        $this->post('/api/agentic/widget/messages', [
            'agent' => 'support',
            'conversation_id' => $conversationId,
            'message' => '',
            'files' => [UploadedFile::fake()->image('250px.png')],
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.handoff', true);

        $this->assertSame($jobsBeforeUpload, Queue::pushed(ProcessWidgetMessageJob::class)->count());

        $history = $this->getJson('/api/agentic/widget/conversations/'.$conversationId.'/messages', $headers)
            ->assertOk();

        $userRow = collect($history->json('data'))->first(fn ($row) => str_contains((string) ($row['html'] ?? ''), '<img'));
        $this->assertIsArray($userRow);
        $html = (string) $userRow['html'];
        $this->assertStringContainsString('class="ag-attach"', $html);
        $this->assertStringContainsString('ag-attach-caption', $html);

        $url = (string) ($userRow['attachments'][0]['url'] ?? '');
        $this->assertNotSame('', $url);

        $this->get($url)->assertOk();

        $this->get($url, ['Authorization' => 'Bearer invalid'])->assertOk();

        $this->get('/api/agentic/widget/conversations/'.$conversationId.'/files/not-a-real-file.png', $headers)
            ->assertNotFound();

        $staff = $this->getJson('/api/agentic/admin/conversations/'.$conversationId.'/messages?limit=80')
            ->assertOk();

        $this->assertGreaterThanOrEqual(2, ConversationMessage::query()->count());
        $this->assertTrue(collect($staff->json('data'))->contains(fn ($row) => str_contains((string) ($row['html'] ?? ''), 'ag-attach')));
    }
}
