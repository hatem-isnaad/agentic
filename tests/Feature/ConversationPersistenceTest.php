<?php

namespace Agentic\Tests\Feature;

use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Conversation\Conversation;
use Agentic\Persistence\Eloquent\EloquentConversationRepository;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

final class ConversationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_eloquent_conversation_repository_round_trips(): void
    {
        $this->app->bind(ConversationRepository::class, EloquentConversationRepository::class);

        $repo = app(ConversationRepository::class);
        $id = (string) Str::uuid();

        $repo->store(new Conversation(
            id: $id,
            agent: 'support',
            userId: '42',
            metadata: ['channel' => 'web'],
        ));

        $found = $repo->find($id);
        $latest = $repo->findLatestFor('support', '42');

        $this->assertNotNull($found);
        $this->assertSame('support', $found->agent);
        $this->assertSame($id, $latest?->id);
    }
}
