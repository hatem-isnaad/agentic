<?php

namespace Agentic\Context;

use Agentic\Widget\Support\WidgetContextPolicy;

final class ContextPolicyResolver
{
    /**
     * @return array<string, mixed>
     */
    public static function forRuntime(RuntimeContext $runtime): array
    {
        if ($runtime->get('channel') === 'widget') {
            $widget = WidgetContextPolicy::resolve();
            if (($widget['enabled'] ?? false) === true) {
                return $widget;
            }
        }

        return AgentContextPolicy::resolve();
    }

    public static function applyIfMissing(RuntimeContext $runtime): RuntimeContext
    {
        $existing = $runtime->get('context_policy');

        if (is_array($existing) && $existing !== []) {
            return $runtime;
        }

        $policy = self::forRuntime($runtime);

        if (($policy['enabled'] ?? false) !== true) {
            return $runtime;
        }

        return $runtime->with('context_policy', $policy);
    }
}
