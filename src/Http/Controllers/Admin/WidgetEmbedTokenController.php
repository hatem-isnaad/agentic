<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Http\Support\AdminLocaleMeta;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Models\WidgetEmbedToken;
use Agentic\Widget\Embed\WidgetEmbedTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WidgetEmbedTokenController
{
    public function __construct(
        private WidgetEmbedTokenService $tokens,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $rows = WidgetEmbedToken::query()
            ->orderByDesc('id')
            ->get()
            ->map(fn (WidgetEmbedToken $t) => $this->tokens->toAdminArray($t))
            ->all();

        return response()->json(AdminPaginator::paginate($rows, $request));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'allowed_agents' => ['nullable', 'array'],
            'allowed_agents.*' => ['string', 'max:120'],
            'allowed_origins' => ['nullable', 'array'],
            'allowed_origins.*' => ['string', 'max:255'],
            'guest_allowed' => ['boolean'],
            'sanctum_allowed' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $created = $this->tokens->create(
            name: $validated['name'],
            allowedAgents: $validated['allowed_agents'] ?? null,
            allowedOrigins: $validated['allowed_origins'] ?? null,
            guestAllowed: $validated['guest_allowed'] ?? true,
            sanctumAllowed: $validated['sanctum_allowed'] ?? true,
            expiresAt: isset($validated['expires_at']) ? new \DateTimeImmutable($validated['expires_at']) : null,
        );

        return response()->json([
            'data' => array_merge($this->tokens->toAdminArray($created['token']), [
                'plain_token' => $created['plain'],
            ]),
            'meta' => AdminLocaleMeta::build(),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $token = WidgetEmbedToken::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'allowed_agents' => ['nullable', 'array'],
            'allowed_agents.*' => ['string', 'max:120'],
            'allowed_origins' => ['nullable', 'array'],
            'allowed_origins.*' => ['string', 'max:255'],
            'guest_allowed' => ['boolean'],
            'sanctum_allowed' => ['boolean'],
            'enabled' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $updated = $this->tokens->update($token, $validated);

        return response()->json([
            'data' => $this->tokens->toAdminArray($updated),
            'meta' => AdminLocaleMeta::build(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $token = WidgetEmbedToken::query()->findOrFail($id);
        $this->tokens->revoke($token);

        return response()->json(['data' => ['revoked' => true]], 200);
    }
}
