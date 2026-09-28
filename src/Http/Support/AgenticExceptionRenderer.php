<?php

namespace Agentic\Http\Support;

use Agentic\Http\Responses\JsonApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as FoundationHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class AgenticExceptionRenderer
{
    public static function register(ExceptionHandler $handler): void
    {
        if (! $handler instanceof FoundationHandler) {
            return;
        }

        $handler->renderable(function (AuthenticationException $e, Request $request) {
            if (! AgenticApiRequest::matches($request)) {
                return null;
            }

            return JsonApiResponse::error(self::message('admin_sign_in_required'), 401);
        });

        $handler->renderable(function (AuthorizationException $e, Request $request) {
            if (! AgenticApiRequest::matches($request)) {
                return null;
            }

            return JsonApiResponse::error(self::message('admin_access_denied'), 403);
        });

        $handler->renderable(function (HttpException $e, Request $request) {
            if (! AgenticApiRequest::matches($request)) {
                return null;
            }

            $status = $e->getStatusCode();
            if ($status === 429 && $e instanceof TooManyRequestsHttpException) {
                return JsonApiResponse::error(self::message('rate_limit'), 429);
            }

            if (! in_array($status, [401, 403], true)) {
                return null;
            }

            $message = trim((string) $e->getMessage());
            if ($message === '' || $message === 'This action is unauthorized.' || $message === 'Unauthenticated.') {
                $message = $status === 401
                    ? self::message('admin_sign_in_required')
                    : self::message('admin_access_denied');
            }

            return JsonApiResponse::error($message, $status);
        });
    }

    private static function message(string $key): string
    {
        $line = __("agentic::errors.{$key}");

        return is_string($line) && $line !== "agentic::errors.{$key}" ? $line : match ($key) {
            'admin_sign_in_required' => 'Please sign in to access Agentic.',
            'admin_access_denied' => 'You do not have permission to access Agentic admin.',
            'rate_limit' => 'Too many requests. Please wait a moment and try again.',
            default => 'Request failed.',
        };
    }
}
