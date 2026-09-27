<?php

namespace Agentic\Widget\Embed;

use Agentic\Models\WidgetEmbedToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class WidgetEmbedCredentialResolver
{
    public function resolveBearer(Request $request): ?string
    {
        $auth = (string) $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return trim($m[1]);
        }

        $header = $request->header('X-Agentic-Embed-Token');

        return is_string($header) && $header !== '' ? trim($header) : null;
    }

    public function findToken(string $plain): ?WidgetEmbedToken
    {
        if (! str_starts_with($plain, 'wgt_')) {
            return null;
        }

        $prefix = substr($plain, 0, 12);

        $candidates = WidgetEmbedToken::query()
            ->where('enabled', true)
            ->where('token_prefix', $prefix)
            ->get();

        foreach ($candidates as $row) {
            if (Hash::check($plain, $row->token_hash)) {
                return $row;
            }
        }

        return null;
    }
}
