<?php

namespace Agentic\Http\Requests\Admin\Concerns;

trait ValidatesAgentPersona
{
    /**
     * @return array<string, mixed>
     */
    protected function personaRules(): array
    {
        return [
            'config.persona' => ['nullable', 'array'],
            'config.persona.display_name' => ['nullable', 'string', 'max:80'],
            'config.persona.gender' => ['nullable', 'string', 'in:'.implode(',', config('agentic.persona.genders', ['male', 'female', 'unspecified']))],
            'config.persona.language' => ['nullable', 'string', 'in:'.implode(',', config('agentic.persona.languages', ['en', 'ar', 'bilingual']))],
            'config.persona.dialect' => ['nullable', 'string', 'in:'.implode(',', config('agentic.persona.dialects', ['msa', 'saudi', 'egyptian', 'gulf', 'levant']))],
            'config.persona.tone' => ['nullable', 'string', 'in:'.implode(',', config('agentic.persona.tones', ['friendly', 'formal', 'casual', 'professional', 'warm']))],
            'config.persona.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
