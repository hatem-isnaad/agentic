<?php

namespace Agentic\Http\Requests\Admin\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesAgentAiSelection
{
    /**
     * @return array<string, mixed>
     */
    protected function agentAiRules(): array
    {
        $providerKeys = array_keys(config('agentic.ai.providers', []));

        return [
            'provider' => ['nullable', 'string', Rule::in($providerKeys)],
            'model' => [
                'nullable',
                'string',
                'max:128',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $provider = $this->input('provider');

                    if (! is_string($provider) || $provider === '') {
                        return;
                    }

                    $models = config("agentic.ai.providers.{$provider}.models", []);

                    if (! is_array($models) || ! in_array($value, $models, true)) {
                        $fail(__('The selected model is not allowed for this provider.'));
                    }
                },
            ],
        ];
    }
}
