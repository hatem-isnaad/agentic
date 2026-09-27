<?php

namespace Agentic\Tests\Unit;

use Agentic\Context\ContextPolicyResolver;
use Agentic\Context\RuntimeContext;
use Agentic\Tests\TestCase;

final class ContextPolicyResolverTest extends TestCase
{
    public function test_widget_channel_uses_stricter_widget_limits(): void
    {
        config()->set('agentic.context.skill_routing_limit', 10);
        config()->set('agentic.widget.context.skill_routing_limit', 2);

        $runtime = ContextPolicyResolver::applyIfMissing(new RuntimeContext(['channel' => 'widget']));
        $policy = $runtime->get('context_policy');

        $this->assertIsArray($policy);
        $this->assertSame(2, $policy['skill_routing_limit']);
    }

    public function test_admin_channel_uses_global_context_limits(): void
    {
        config()->set('agentic.context.skill_routing_limit', 6);
        config()->set('agentic.widget.context.skill_routing_limit', 2);

        $runtime = ContextPolicyResolver::applyIfMissing(new RuntimeContext([]));
        $policy = $runtime->get('context_policy');

        $this->assertIsArray($policy);
        $this->assertSame(6, $policy['skill_routing_limit']);
    }
}
