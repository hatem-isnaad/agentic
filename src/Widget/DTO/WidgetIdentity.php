<?php

namespace Agentic\Widget\DTO;

use Agentic\Widget\Embed\WidgetEmbedRequestValidator;
use Illuminate\Http\Request;

final readonly class WidgetIdentity
{
    public function __construct(
        public ?string $guestId,
        public ?string $userIdentity,
        public ?string $tenantId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validator = app(WidgetEmbedRequestValidator::class);
        $tenant = $request->header('X-Agentic-Tenant-Id');

        return new self(
            $validator->resolveGuestId($request),
            $validator->resolveUserIdentity($request),
            is_string($tenant) && $tenant !== '' ? $tenant : null,
        );
    }

    public function conversationUserId(): ?string
    {
        if (is_string($this->userIdentity) && $this->userIdentity !== '') {
            return $this->userIdentity;
        }

        return is_string($this->guestId) && $this->guestId !== '' ? 'guest:'.$this->guestId : null;
    }
}
