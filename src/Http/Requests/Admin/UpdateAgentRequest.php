<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Admin\DTO\AgentData;
use Agentic\Http\Requests\Admin\Concerns\NormalizesCommaSeparatedLists;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAgentRequest extends FormRequest
{
    use NormalizesCommaSeparatedLists;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->route('slug'),
        ]);

        $this->normalizeCommaSeparatedLists(['skills', 'tools', 'permissions']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return (new StoreAgentRequest)->rules();
    }

    public function toData(): AgentData
    {
        return AgentData::fromValidated($this->validated());
    }
}
