<?php

namespace Agentic\Context\Providers;

use Agentic\Context\Contracts\ContextProvider;
use Agentic\Context\RuntimeContext;
use Illuminate\Http\Request;

final class HttpRequestContextProvider implements ContextProvider
{
    public function provide(RuntimeContext $context): array
    {
        $request = $context->get('request');

        if (! $request instanceof Request) {
            $request = app()->bound('request') ? request() : null;
        }

        if (! $request instanceof Request) {
            return [];
        }

        $values = [];

        if (! $context->has('request')) {
            $values['request'] = $request;
        }

        $user = $request->user();

        if ($user === null) {
            return $values;
        }

        if (! $context->has('user')) {
            $values['user'] = $user;
        }

        if (! $context->has('user_id')) {
            $id = $user->getAuthIdentifier();

            if (is_string($id) || is_int($id)) {
                $values['user_id'] = $id;
            }
        }

        return $values;
    }
}
