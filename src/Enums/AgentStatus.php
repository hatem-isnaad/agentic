<?php

namespace Agentic\Enums;

enum AgentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
