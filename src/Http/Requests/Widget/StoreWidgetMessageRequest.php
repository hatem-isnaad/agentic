<?php

namespace Agentic\Http\Requests\Widget;

use Agentic\Http\Requests\Widget\Concerns\ValidatesWidgetAgent;
use Agentic\Widget\DTO\WidgetIdentity;
use Agentic\Widget\DTO\WidgetMessageData;
use Illuminate\Foundation\Http\FormRequest;

final class StoreWidgetMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:8000'],
            'conversation_id' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:16'],
            'metadata' => ['nullable', 'array', 'max:32'],
        ];
    }

    public function toData(?string $conversationId = null): WidgetMessageData
    {
        $validated = $this->validated();

        return new WidgetMessageData(agent: $validated['agent'], message: $validated['message'], identity: WidgetIdentity::fromRequest($this), conversationId: $conversationId ?? ($validated['conversation_id'] ?? null), metadata: $validated['metadata'] ?? []);
    }
}
