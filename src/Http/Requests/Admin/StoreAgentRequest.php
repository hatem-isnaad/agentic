<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Admin\DTO\AgentData;
use Agentic\Http\Requests\Admin\Concerns\NormalizesCommaSeparatedLists;
use Agentic\Http\Requests\Admin\Concerns\ValidatesAgentAiSelection;
use Agentic\Http\Requests\Admin\Concerns\ValidatesAgentPersona;
use Illuminate\Foundation\Http\FormRequest;

final class StoreAgentRequest extends FormRequest
{
    use NormalizesCommaSeparatedLists;
    use ValidatesAgentAiSelection;
    use ValidatesAgentPersona;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeCommaSeparatedLists(['skills', 'tools', 'knowledge', 'permissions']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            ...$this->agentAiRules(),
            'temperature' => ['nullable', 'numeric'],
            'max_tokens' => ['nullable', 'integer', 'min:1'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string'],
            'knowledge' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'runtime' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
            ...$this->personaRules(),
        ];
    }

    public function toData(): AgentData
    {
        return AgentData::fromValidated($this->validated());
    }
}
