<?php

namespace Agentic\Http\Responses;

use Illuminate\Http\JsonResponse;

final class JsonApiResponse
{
    public static function data(mixed $data, int $status = 200, ?array $meta = null): JsonResponse
    {
        $payload = ['data' => $data];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function created(mixed $data): JsonResponse
    {
        return self::data($data, 201);
    }

    public static function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
