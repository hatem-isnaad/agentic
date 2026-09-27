<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Admin\DTO\ToolData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateToolRequest extends FormRequest
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
        return (new StoreToolRequest)->rules();
    }

    public function toData(): ToolData
    {
        return ToolData::fromValidated($this->validated());
    }
}
