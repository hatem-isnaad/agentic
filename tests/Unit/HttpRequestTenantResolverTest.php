<?php

namespace Agentic\Tests\Unit;

use Agentic\Tenancy\HttpRequestTenantResolver;
use Agentic\Tests\TestCase;
use Illuminate\Http\Request;

final class HttpRequestTenantResolverTest extends TestCase
{
    public function test_resolves_tenant_from_configured_header(): void
    {
        $request = Request::create('/api/agentic/agents/support/execute', 'POST');
        $request->headers->set('X-Agentic-Tenant-Id', 'acme');

        $this->assertSame('acme', (new HttpRequestTenantResolver())->resolve($request));
    }

    public function test_resolves_tenant_from_authenticated_user_attribute(): void
    {
        $user = new class {
            public string $tenant_id = 'org-9';

            public function getAuthIdentifier(): int
            {
                return 1;
            }
        };

        $request = Request::create('/api/agentic/agents/support/execute', 'POST');
        $request->setUserResolver(fn () => $user);

        $this->assertSame('org-9', (new HttpRequestTenantResolver())->resolve($request));
    }
}
