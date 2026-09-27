<?php

namespace Agentic\Agent;

use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\AgentExecutionResult;

interface AgentContract
{
    public function definition(): AgentDefinition;

    public function run(AgentExecutionContext $context): AgentExecutionResult;
}
