<?php

namespace Agentic\Tests\Unit;

use Agentic\Models\WidgetEmbedToken;
use Agentic\Tests\TestCase;
use Agentic\Widget\DTO\WidgetIdentity;
use Agentic\Widget\Embed\WidgetEmbedRequestValidator;
use Illuminate\Http\Request;

final class WidgetIdentitySpoofingTest extends TestCase
{
    public function test_user_header_is_not_treated_as_identity(): void
    {
        $request = Request::create('/api/agentic/widget/messages', 'POST');
        $request->headers->set('X-Agentic-User-Id', '123');
        $request->headers->set('X-Agentic-Guest-Id', 'guest-1');

        $validator = app(WidgetEmbedRequestValidator::class);

        $this->assertNull($validator->resolveUserIdentity($request));
        $this->assertSame('guest-1', $validator->resolveGuestId($request));

        $identity = WidgetIdentity::fromRequest($request);
        $this->assertSame('guest:guest-1', $identity->conversationUserId());
        $this->assertNull($identity->userIdentity);
    }

    public function test_authenticated_user_is_the_only_user_identity(): void
    {
        $user = new class
        {
            public function getAuthIdentifier(): int
            {
                return 99;
            }
        };

        $request = Request::create('/api/agentic/widget/messages', 'POST');
        $request->headers->set('X-Agentic-User-Id', '123');
        $request->setUserResolver(fn () => $user);

        $this->assertSame('user:99', app(WidgetEmbedRequestValidator::class)->resolveUserIdentity($request));
    }

    public function test_auth_required_token_rejects_user_header_without_session(): void
    {
        $embed = new WidgetEmbedToken([
            'guest_allowed' => false,
            'sanctum_allowed' => true,
            'enabled' => true,
        ]);

        $request = Request::create('/api/agentic/widget/config', 'GET');
        $request->headers->set('X-Agentic-User-Id', '123');

        $result = app(WidgetEmbedRequestValidator::class)->validateIdentity($request, $embed);

        $this->assertFalse($result['ok']);
    }
}
