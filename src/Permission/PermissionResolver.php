<?php

namespace Agentic\Permission;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\RuntimeContext;
use Agentic\Tool\Contracts\ToolContract;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves whether a Tool may execute.
 *
 * Default is DENY unless explicitly allowed.
 * LLM output never authorizes itself.
 */
final class PermissionResolver
{
    public function __construct(
        private PermissionChecker $checker,
        private Container $container,
    ) {}

    public function decide(
        ToolContract $tool,
        ?AgentDefinition $agent = null,
        ?RuntimeContext $runtime = null,
    ): PermissionDecision {
        $ability = 'tool:'.$tool->definition()->name;

        // Tool-level allow-list on the definition.
        $toolPermissions = $tool->definition()->permissions;
        if ($toolPermissions !== [] && ! $this->matches($ability, $toolPermissions)) {
            return PermissionDecision::Deny;
        }

        // Agent-level allow-list.
        if ($agent !== null && $agent->permissions !== []) {
            if (! $this->matches($ability, $agent->permissions)) {
                return PermissionDecision::Deny;
            }
        }

        if ($this->checker->allows($ability, $tool)) {
            return PermissionDecision::Allow;
        }

        if ($this->gateAllows($ability, $tool, $runtime)) {
            return PermissionDecision::Allow;
        }

        return (string) config('agentic.permissions.default', 'deny') === 'allow'
            ? PermissionDecision::Allow
            : PermissionDecision::Deny;
    }

    public function denialMessage(ToolContract $tool): string
    {
        return $this->checker->denialMessage('tool:'.$tool->definition()->name, $tool);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function matches(string $ability, array $permissions): bool
    {
        if (in_array('*', $permissions, true)) {
            return true;
        }

        $tool = str_starts_with($ability, 'tool:') ? substr($ability, 5) : $ability;

        return in_array($ability, $permissions, true)
            || in_array($tool, $permissions, true);
    }

    private function gateAllows(string $ability, ToolContract $tool, ?RuntimeContext $runtime): bool
    {
        if (! $this->container->bound(Gate::class)) {
            return false;
        }

        try {
            /** @var Gate $gate */
            $gate = $this->container->make(Gate::class);
            $user = $runtime?->user();

            return $gate->forUser($user)->check($ability, $tool);
        } catch (\Throwable) {
            return false;
        }
    }
}
