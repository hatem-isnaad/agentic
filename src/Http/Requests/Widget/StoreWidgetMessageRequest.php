<?php

namespace Agentic\Http\Requests\Widget;

use Agentic\Http\Requests\Widget\Concerns\ValidatesWidgetAgent;
use Agentic\Widget\DTO\WidgetIdentity;
use Agentic\Widget\DTO\WidgetMessageData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

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
            'message' => ['nullable', 'string', 'max:8000', 'required_without:files'],
            'conversation_id' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:16'],
            'metadata' => ['nullable', 'array', 'max:32'],
            'files' => ['nullable', 'array', 'max:3'],
            'files.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif,pdf'],
        ];
    }

    public function toData(?string $conversationId = null): WidgetMessageData
    {
        $validated = $this->validated();

        return new WidgetMessageData(
            agent: $validated['agent'],
            message: trim((string) ($validated['message'] ?? '')) ?: 'Please look at this file.',
            identity: WidgetIdentity::fromRequest($this),
            conversationId: $conversationId ?? ($validated['conversation_id'] ?? null),
            metadata: $validated['metadata'] ?? [],
            files: $this->uploadedFiles(),
        );
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedFiles(): array
    {
        $files = $this->file('files');
        if ($files === null) {
            return [];
        }

        return is_array($files) ? array_values($files) : [$files];
    }
}
