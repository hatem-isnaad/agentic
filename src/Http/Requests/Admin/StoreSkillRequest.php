<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Admin\DTO\SkillData;
use Agentic\Http\Requests\Admin\Concerns\NormalizesCommaSeparatedLists;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSkillRequest extends FormRequest
{
    use NormalizesCommaSeparatedLists;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeCommaSeparatedLists(['tools']);
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
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string'],
            'knowledge' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
        ];
    }

    public function toData(): SkillData
    {
        return SkillData::fromValidated($this->validated());
    }
}
