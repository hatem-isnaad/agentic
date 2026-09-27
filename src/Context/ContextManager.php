<?php

namespace Agentic\Context;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\Contracts\ContextProvider;
use Agentic\Conversation\Conversation;

/**
 * Builds RuntimeContext from registered providers plus request-scoped values.
 */
final class ContextManager
{
    /** @var list<ContextProvider> */
    private array $providers = [];

    public function extend(ContextProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @param  array<string, mixed>  $seed
     */
    public function build(
        array $seed = [],
        ?AgentDefinition $agent = null,
        ?Conversation $conversation = null,
    ): RuntimeContext {
        $context = new RuntimeContext($seed);

        if ($agent !== null) {
            $context = $context->with('agent', $agent);
        }

        if ($conversation !== null) {
            $context = $context->with('conversation', $conversation);
        }

        foreach ($this->providers as $provider) {
            $context = $context->merge($provider->provide($context));
        }

        return $context;
    }

    /** @return list<ContextProvider> */
    public function providers(): array
    {
        return $this->providers;
    }
}
