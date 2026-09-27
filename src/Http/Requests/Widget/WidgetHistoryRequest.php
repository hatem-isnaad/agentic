<?php

namespace Agentic\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

final class WidgetHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = max(1, (int) config('agentic.widget.history.max_page_size', 50));

        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$max],
            'before' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function limit(): int
    {
        $default = (int) config('agentic.widget.history.page_size', 20);

        return (int) ($this->validated('limit') ?? $default);
    }

    public function beforeCursor(): ?int
    {
        $before = $this->validated('before') ?? null;

        return is_numeric($before) ? (int) $before : null;
    }
}
