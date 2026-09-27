<?php

namespace Agentic\Http\Requests\Widget\Concerns;

trait ValidatesWidgetAgent
{
    /**
     * @return array<string, list<string>>
     */
    protected function agentRules(string $presence = 'required'): array
    {
        return ['agent' => [$presence, 'string', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/']];
    }
}
