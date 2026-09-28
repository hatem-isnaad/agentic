<?php

namespace Agentic\Http\Requests\Admin;

use Agentic\Connections\ConnectionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateConnectionRequest extends FormRequest
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
            'slug' => ['sometimes', 'string', 'max:80', 'alpha_dash', Rule::unique('agentic_connections', 'slug')->ignore($id)],
            'type' => ['sometimes', Rule::in(ConnectionService::types())],
            'status' => ['nullable', 'string', 'max:32'],
            'token' => ['nullable', 'string', 'max:2000'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:2000'],
            'name_key' => ['nullable', 'string', 'max:80'],
            'value' => ['nullable', 'string', 'max:2000'],
            'grant_type' => ['nullable', 'in:refresh_token,client_credentials'],
            'token_url' => ['nullable', 'url', 'max:500'],
            'scope' => ['nullable', 'string', 'max:500'],
            'expiry_skew' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:2000'],
            'refresh_token' => ['nullable', 'string', 'max:4000'],
            'access_token' => ['nullable', 'string', 'max:4000'],
            'config' => ['nullable', 'array'],
            'credentials' => ['nullable', 'array'],
            'headers' => ['nullable', 'array'],
            'query' => ['nullable', 'array'],
            'auth_headers' => ['nullable', 'array'],
            'auth_query' => ['nullable', 'array'],
            'auth_body' => ['nullable', 'array'],
        ];
    }
}
