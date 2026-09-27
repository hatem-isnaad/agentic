<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\ContextManager;
use Agentic\Context\RuntimeContext;
use Agentic\Conversation\Conversation;
use Agentic\Tests\TestCase;
use Illuminate\Http\Request;

final class ContextManagerTenantTest extends TestCase
{
    public function test_http_provider_injects_tenant_from_request_header(): void
    {
        $request = Request::create('/test', 'POST');
        $request->headers->set('X-Agentic-Tenant-Id', 'tenant-42');

        $context = app(ContextManager::class)->build(
            seed: ['request' => $request],
            agent: new AgentDefinition(name: 'Support', instructions: 'Help.'),
        );

        $this->assertSame('tenant-42', $context->get('tenant_id'));
        $this->assertSame('tenant-42', $context->tenant());
    }

    public function test_conversation_provider_injects_tenant_from_conversation_record(): void
    {
        $conversation = new Conversation(
            id: 'conv-1',
            agent: 'support',
            tenantId: 'tenant-99',
            userId: 'user-1',
        );

        $context = app(ContextManager::class)->build(
            seed: [],
            conversation: $conversation,
        );

        $this->assertSame('tenant-99', $context->get('tenant_id'));
        $this->assertSame('user-1', $context->get('user_id'));
    }

    public function test_existing_runtime_seed_is_not_overwritten(): void
    {
        $request = Request::create('/test', 'POST');
        $request->headers->set('X-Agentic-Tenant-Id', 'header-tenant');

        $context = app(ContextManager::class)->build(
            seed: [
                'request' => $request,
                'tenant_id' => 'seed-tenant',
            ],
        );

        $this->assertSame('seed-tenant', $context->get('tenant_id'));
    }
}
