<?php

namespace Agentic\Tests\Unit;

use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Routing\AgentRouter;
use Agentic\Routing\RoutingContext;
use Agentic\Routing\Strategies\CallbackRoutingStrategy;
use Agentic\Routing\Strategies\KeywordRoutingStrategy;
use Agentic\Routing\Strategies\SlugRoutingStrategy;
use Agentic\Tests\TestCase;

final class AgentRouterTest extends TestCase
{
    public function test_slug_strategy_routes_explicit_hint(): void
    {
        $router = (new AgentRouter())->use(new SlugRoutingStrategy());

        $result = $router->route(new RoutingContext(
            message: 'anything',
            agentHint: 'support',
        ));

        $this->assertSame('support', $result->agent);
        $this->assertSame('slug', $result->strategy);
    }

    public function test_keyword_strategy_routes_by_message_content(): void
    {
        $router = (new AgentRouter())
            ->use(new KeywordRoutingStrategy([
                'support' => ['refund', 'broken'],
                'sales' => ['pricing', 'quote'],
            ]));

        $result = $router->route(new RoutingContext(
            message: 'I need a pricing quote please',
        ));

        $this->assertSame('sales', $result->agent);
        $this->assertContains('pricing', $result->metadata['matched']);
    }

    public function test_fallback_and_callback_strategies(): void
    {
        $router = (new AgentRouter(fallbackAgent: 'general'))
            ->use(new CallbackRoutingStrategy(fn () => null))
            ->use(new KeywordRoutingStrategy([]));

        $result = $router->route(new RoutingContext(message: 'hello'));

        $this->assertSame('general', $result->agent);
        $this->assertSame('fallback', $result->strategy);
    }

    public function test_router_throws_when_no_match_and_no_fallback(): void
    {
        $this->expectException(AgentNotFoundException::class);

        (new AgentRouter())
            ->use(new SlugRoutingStrategy())
            ->route(new RoutingContext(message: 'nope'));
    }
}
