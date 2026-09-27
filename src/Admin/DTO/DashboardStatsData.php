<?php

namespace Agentic\Admin\DTO;

final readonly class DashboardStatsData
{
    public function __construct(
        public int $agents,
        public int $skills,
        public int $tools,
        public int $knowledgeSources,
        public int $workflows,
        public int $executions,
        public int $conversations,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'agents' => $this->agents,
            'skills' => $this->skills,
            'tools' => $this->tools,
            'knowledge_sources' => $this->knowledgeSources,
            'workflows' => $this->workflows,
            'executions' => $this->executions,
            'conversations' => $this->conversations,
        ];
    }
}
