<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\DashboardStatsData;
use Agentic\Contracts\Repositories\AgentRepository;
use Agentic\Contracts\Repositories\ConversationRepository;
use Agentic\Contracts\Repositories\ExecutionRepository;
use Agentic\Contracts\Repositories\KnowledgeRepository;
use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Contracts\Repositories\ToolRepository;

final class DashboardAdminService
{
    public function __construct(
        private AgentRepository $agents,
        private SkillRepository $skills,
        private ToolRepository $tools,
        private KnowledgeRepository $knowledge,
        private ExecutionRepository $executions,
        private ConversationRepository $conversations,
    ) {}

    public function stats(): DashboardStatsData
    {
        return new DashboardStatsData(
            agents: count($this->agents->all()),
            skills: count($this->skills->all()),
            tools: count($this->tools->all()),
            knowledgeSources: count($this->knowledge->all()),
            executions: count($this->executions->recent(1000)),
            conversations: count($this->conversations->recent(1000)),
        );
    }
}
