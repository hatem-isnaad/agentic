<?php

namespace Agentic\Http\Requests\Widget;

use Agentic\Http\Requests\Widget\Concerns\ValidatesWidgetAgent;
use Illuminate\Foundation\Http\FormRequest;

final class WidgetAgentQueryRequest extends FormRequest
{
    use ValidatesWidgetAgent;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->agentRules();
    }

    public function agentSlug(): string
    {
        return (string) $this->validated('agent');
    }
}
