<?php

namespace Agentic\Tests\Unit;

use Agentic\Http\Middleware\AuthorizeAgenticAdmin;
use Agentic\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AuthorizeAgenticAdminTest extends TestCase
{
    public function test_skips_authorization_when_gate_config_empty(): void
    {
        config(['agentic.admin.authorization.gate' => null]);

        $middleware = new AuthorizeAgenticAdmin();
        $response = $middleware->handle(Request::create('/'), fn () => response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_throws_when_gate_denies(): void
    {
        config(['agentic.admin.authorization.gate' => 'viewAgenticDeny']);
        Gate::define('viewAgenticDeny', fn () => false);

        $middleware = new AuthorizeAgenticAdmin();

        $this->expectException(AuthorizationException::class);

        $middleware->handle(Request::create('/'), fn () => response('ok', 200));
    }

    public function test_allows_when_gate_passes(): void
    {
        config(['agentic.admin.authorization.gate' => 'viewAgenticOk']);
        Gate::define('viewAgenticOk', fn ($user = null) => true);

        $middleware = new AuthorizeAgenticAdmin();
        $response = $middleware->handle(Request::create('/'), fn () => response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
    }
}
