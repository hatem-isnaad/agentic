<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\ContextManager;
use Agentic\Context\Providers\ArrayContextProvider;
use Agentic\Conversation\ConversationManager;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Runtime\AgentRuntime;
use Agentic\Tests\TestCase;
use Laravel\Ai\AnonymousAgent;

final class ConversationManagerTest extends TestCase
{
    public function test_continue_or_start_reuses_latest_conversation(): void
    {
        $manager = app(ConversationManager::class);

        $first = $manager->start('support', userId: 7);
        $again = $manager->continueOrStart('support', userId: 7);

        $this->assertSame($first->id, $again->id);
    }

    public function test_runtime_binds_conversation_into_context_and_execution(): void
    {
        AnonymousAgent::fake(['Hi again']);

        app(ContextManager::class)->extend(new ArrayContextProvider([
            'locale' => 'en',
            'timezone' => 'UTC',
        ]));

        $conversation = app(ConversationManager::class)->start('support', userId: 1);

        $result = app(AgentRuntime::class)->run(
            new AgentDefinition(name: 'Support', slug: 'support', instructions: 'Help.'),
            new AgentExecutionContext(
                message: 'Hello',
                conversation: $conversation,
            ),
        );

        $this->assertTrue($result->success);
        $this->assertSame($conversation->id, $result->output['conversation_id']);
    }
}
