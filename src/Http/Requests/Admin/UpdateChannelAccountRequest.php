<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Channels\ChannelDriver;
use Agentic\Channels\ChannelKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateChannelAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:80', 'alpha_dash', Rule::unique('agentic_channel_accounts', 'slug')->ignore($id)],
            'channel' => ['sometimes', Rule::in(ChannelKind::values())],
            'driver' => ['sometimes', Rule::in(ChannelDriver::values())],
            'agent_slug' => ['nullable', 'string', 'max:120'],
            'external_id' => ['nullable', 'string', 'max:120'],
            'display_number' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:32'],
            'config' => ['nullable', 'array'],
            'credentials' => ['nullable', 'array'],
        ];
    }
}
