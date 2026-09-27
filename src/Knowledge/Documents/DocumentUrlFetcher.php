<?php

namespace Agentic\Knowledge\Documents;

use Agentic\Tool\Drivers\Http\HttpUrlValidator;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class DocumentUrlFetcher
{
    public function __construct(
        private HttpUrlValidator $urls = new HttpUrlValidator(),
    ) {}

    /**
     * @param  list<string>  $urls
     * @return list<string>
     */
    public function fetchMany(array $urls): array
    {
        if (! (bool) config('agentic.knowledge.fetch.enabled', true)) {
            throw new InvalidArgumentException('Knowledge URL fetch is disabled.');
        }

        $documents = [];

        foreach ($urls as $url) {
            if (! is_string($url) || trim($url) === '') {
                continue;
            }

            $url = trim($url);
            $this->urls->validate($url);

            $response = Http::timeout((int) config('agentic.knowledge.fetch.timeout', 15))
                ->withOptions(['allow_redirects' => (bool) config('agentic.http.allow_redirects', false)])
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("Failed to fetch knowledge URL [{$url}] (HTTP {$response->status()}).");
            }

            $body = $response->body();
            $maxBytes = (int) config(
                'agentic.knowledge.fetch.max_bytes',
                config('agentic.http.max_response_bytes', 5 * 1024 * 1024),
            );

            if (strlen($body) > $maxBytes) {
                throw new RuntimeException("Knowledge URL [{$url}] exceeded the maximum response size.");
            }

            $documents[] = trim($url."\n\n".$body);
        }

        return $documents;
    }
}
