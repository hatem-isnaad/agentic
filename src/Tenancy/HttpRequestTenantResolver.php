<?php

namespace Agentic\Tenancy;

use Agentic\Contracts\TenantResolver;
use Illuminate\Http\Request;

final class HttpRequestTenantResolver implements TenantResolver
{
    public function resolve(?Request $request = null): string|int|null
    {
        $request ??= $this->currentRequest();

        if ($request === null) {
            return null;
        }

        $header = (string) config('agentic.tenant.header', 'X-Agentic-Tenant-Id');
        $fromHeader = $request->header($header);

        if (is_string($fromHeader) && $fromHeader !== '') {
            return $fromHeader;
        }

        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $attribute = (string) config('agentic.tenant.user_attribute', 'tenant_id');

        if ($attribute !== '' && isset($user->{$attribute})) {
            $value = $user->{$attribute};

            if (is_string($value) || is_int($value)) {
                return $value;
            }
        }

        return null;
    }

    private function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request instanceof Request ? $request : null;
    }
}
