<?php

namespace Agentic\Tool\Drivers\Http;

use Illuminate\Http\Client\Response;

/**
 * Normalizes HTTP responses and optionally maps fields into a Tool result payload.
 */
final class ResponseMapper
{
    /**
     * @param  array<string, mixed>  $mapping
     */
    public function map(Response $response, array $mapping = []): mixed
    {
        $json = $response->json();
        $payload = [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $json ?? $response->body(),
        ];

        if ($mapping === []) {
            return $payload['body'];
        }

        $mapped = [];

        foreach ($mapping as $target => $source) {
            if (! is_string($target) || ! is_string($source)) {
                continue;
            }

            $mapped[$target] = match (true) {
                $source === 'status' => $payload['status'],
                $source === 'body' => $payload['body'],
                str_starts_with($source, 'body.') => data_get($payload['body'], substr($source, 5)),
                str_starts_with($source, 'headers.') => data_get($payload['headers'], substr($source, 8)),
                default => data_get($payload, $source),
            };
        }

        return $mapped;
    }
}
