<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Admin\DTO\ToolData;
use Illuminate\Foundation\Http\FormRequest;

final class StoreToolRequest extends FormRequest
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
            'driver' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'config' => ['nullable', 'array'],
            'definition' => ['nullable', 'array'],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): ToolData
    {
        return ToolData::fromValidated($this->validated());
    }
}
