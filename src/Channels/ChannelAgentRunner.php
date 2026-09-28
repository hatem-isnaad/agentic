<?php

namespace Agentic\Channels;

use Agentic\Agent\AgentDefinition;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Execution\AgentExecutionResult;

interface ChannelAgentRunner
{
    public function run(AgentDefinition $agent, AgentExecutionContext $context): AgentExecutionResult;
}
