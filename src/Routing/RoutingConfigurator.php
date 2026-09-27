<?php

namespace Agentic\Routing;

use Agentic\Routing\Strategies\KeywordRoutingStrategy;
use Agentic\Routing\Strategies\LlmAgentRoutingStrategy;
use Agentic\Routing\Strategies\SlugRoutingStrategy;
use Agentic\Routing\Support\AgentCatalogBuilder;
use Agentic\Routing\Support\AgentLlmSelector;

/**
 * Registers default deterministic + optional LLM routing strategies on AgentRouter.
 */
final class RoutingConfigurator
{
    public function configure(AgentRouter $router): void
    {
        if (config('agentic.routing.slug_hint', true)) {
            $router->use(new SlugRoutingStrategy());
        }

        $keywordMap = config('agentic.routing.keyword_map', []);

        if (is_array($keywordMap) && $keywordMap !== []) {
            $router->use(new KeywordRoutingStrategy($keywordMap));
        }

        if (config('agentic.routing.llm.enabled', false)) {
            $router->use(new LlmAgentRoutingStrategy(
                selector: app(AgentLlmSelector::class),
                catalog: app(AgentCatalogBuilder::class)->published(),
                provider: config('agentic.routing.llm.provider'),
                model: config('agentic.routing.llm.model'),
            ));
        }
    }
}
