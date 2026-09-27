<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Admin\DTO\KnowledgeSourceData;
use Illuminate\Foundation\Http\FormRequest;

final class StoreKnowledgeSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'driver' => ['nullable', 'string', 'in:array,vector'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'config' => ['nullable', 'array'],
        ];
    }

    public function toData(): KnowledgeSourceData
    {
        return KnowledgeSourceData::fromValidated($this->validated());
    }
}
