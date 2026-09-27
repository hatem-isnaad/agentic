<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Admin\DTO\KnowledgeSourceData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateKnowledgeSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->route('slug'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return (new StoreKnowledgeSourceRequest)->rules();
    }

    public function toData(): KnowledgeSourceData
    {
        return KnowledgeSourceData::fromValidated($this->validated());
    }
}
