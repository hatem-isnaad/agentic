<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Channels\ChannelDriver;
use Agentic\Channels\ChannelKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreChannelAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:agentic_channel_accounts,slug'],
            'channel' => ['required', Rule::in(ChannelKind::values())],
            'driver' => ['required', Rule::in(ChannelDriver::values())],
            'agent_slug' => ['nullable', 'string', 'max:120'],
            'external_id' => ['nullable', 'string', 'max:120'],
            'display_number' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:32'],
            'config' => ['nullable', 'array'],
            'credentials' => ['nullable', 'array'],
        ];
    }
}
