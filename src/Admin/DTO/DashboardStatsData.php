<?php

namespace Agentic\Admin\DTO;

final readonly class DashboardStatsData
{
    public function __construct(
        public int $agents,
        public int $skills,
        public int $tools,
        public int $knowledgeSources,
        public int $executions,
        public int $conversations,
    ) {}
}
