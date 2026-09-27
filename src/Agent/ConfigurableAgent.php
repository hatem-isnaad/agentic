<?php

namespace Agentic\Agent;

use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\AgentExecutionResult;
use Agentic\Runtime\AgentRuntime;

/**
 * Runtime agent backed by an AgentDefinition.
 *
 * Execution is always delegated to AgentRuntime → Laravel AI SDK.
 */
final class ConfigurableAgent implements AgentContract
{
    public function __construct(
        private AgentDefinition $definition,
        private AgentRuntime $runtime,
    ) {}

    public function definition(): AgentDefinition
    {
        return $this->definition;
    }

    public function run(AgentExecutionContext $context): AgentExecutionResult
    {
        return $this->runtime->run($this->definition, $context);
    }
}
