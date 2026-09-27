<?php

namespace Agentic\Http\Requests\Widget;

use Agentic\Http\Requests\Widget\Concerns\ValidatesWidgetAgent;
use Agentic\Widget\DTO\WidgetConversationData;
use Agentic\Widget\DTO\WidgetIdentity;
use Illuminate\Foundation\Http\FormRequest;

final class StoreWidgetConversationRequest extends FormRequest
{
    use ValidatesWidgetAgent;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->agentRules(),
            'locale' => ['nullable', 'string', 'max:16'],
            'metadata' => ['nullable', 'array', 'max:32'],
        ];
    }

    public function toData(): WidgetConversationData
    {
        $validated = $this->validated();

        return new WidgetConversationData(
            agent: $validated['agent'],
            identity: WidgetIdentity::fromRequest($this),
            metadata: $validated['metadata'] ?? [],
        );
    }
}
