<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Admin\DTO\SkillData;
use Agentic\Http\Requests\Admin\Concerns\NormalizesCommaSeparatedLists;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSkillRequest extends FormRequest
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

        $this->normalizeCommaSeparatedLists(['tools']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return (new StoreSkillRequest)->rules();
    }

    public function toData(): SkillData
    {
        return SkillData::fromValidated($this->validated());
    }
}
