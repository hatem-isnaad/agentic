<?php

namespace Agentic\Context\Providers;

use Agentic\Context\Contracts\ContextProvider;
use Agentic\Context\RuntimeContext;
use Agentic\Conversation\Conversation;

final class ConversationContextProvider implements ContextProvider
{
    public function provide(RuntimeContext $context): array
    {
        $conversation = $context->conversation();

        if (! $conversation instanceof Conversation) {
            return [];
        }

        $values = [];

        if ($conversation->tenantId !== null) {
            if (! $context->has('tenant_id')) {
                $values['tenant_id'] = $conversation->tenantId;
            }

            if (! $context->has('tenant')) {
                $values['tenant'] = $conversation->tenantId;
            }
        }

        if ($conversation->userId !== null && ! $context->has('user_id')) {
            $values['user_id'] = $conversation->userId;
        }

        return $values;
    }
}
