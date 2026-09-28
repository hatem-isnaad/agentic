<?php

namespace Agentic\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'conversation_id' => ['nullable', 'string', 'max:64'],
            'execution_id' => ['nullable', 'string', 'max:64'],
            'agent_slug' => ['nullable', 'string', 'max:120'],
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'label' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
