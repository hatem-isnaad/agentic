<?php

namespace Agentic\Tool;

use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Tool\Drivers\Http\HttpRequestBuilder;
use InvalidArgumentException;
use Throwable;

final class HttpToolTester
{
    public function __construct(
        private ToolRepository $tools,
        private DriverResolver $drivers,
        private HttpRequestBuilder $requests,
    ) {}

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{success: bool, data: mixed, error: ?string, duration_ms: int, request: ?array<string, mixed>}
     */
    public function run(string $slug, array $arguments = []): array
    {
        $definition = $this->tools->findBySlug($slug);

        if ($definition === null) {
            throw new ToolNotFoundException($slug);
        }

        if ($definition->driver !== 'http') {
            throw new InvalidArgumentException("Tool [{$slug}] is not an HTTP tool.");
        }

        $url = $definition->configuration['url'] ?? null;

        if (! is_string($url) || $url === '') {
            throw new InvalidArgumentException("HTTP tool [{$slug}] has no published URL. Save it with Publish version on.");
        }

        $context = new ToolExecutionContext(arguments: $arguments);
        $preview = null;

        try {
            $preview = $this->redactRequest($this->requests->build($definition, $context));
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'data' => null,
                'error' => $exception->getMessage(),
                'duration_ms' => 0,
                'request' => $preview,
            ];
        }

        $started = microtime(true);
        $result = (new ConfiguredTool($definition, $this->drivers))->execute($context);

        return [
            'success' => $result->success,
            'data' => $result->data,
            'error' => $result->error,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'request' => $preview,
        ];
    }

    /**
     * @return list<array{name: string, type: string, required: bool, description: string}>
     */
    public function argumentFields(string $slug): array
    {
        $definition = $this->tools->findBySlug($slug);

        if ($definition === null) {
            throw new ToolNotFoundException($slug);
        }

        return self::fieldsFromSchema($definition->inputSchema);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<array{name: string, type: string, required: bool, description: string}>
     */
    public static function fieldsFromSchema(array $schema): array
    {
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : $schema;
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
        $fields = [];

        foreach ($properties as $name => $definition) {
            if (! is_string($name) || $name === '' || in_array($name, ['type', 'properties', 'required'], true)) {
                continue;
            }

            $meta = is_array($definition) ? $definition : [];
            $fields[] = [
                'name' => $name,
                'type' => is_string($meta['type'] ?? null) ? $meta['type'] : 'string',
                'required' => in_array($name, $required, true) || ($meta['required'] ?? false) === true,
                'description' => is_string($meta['description'] ?? null) ? $meta['description'] : '',
            ];
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    private function redactRequest(array $request): array
    {
        $headers = [];

        foreach (is_array($request['headers'] ?? null) ? $request['headers'] : [] as $key => $value) {
            $headers[(string) $key] = $this->isSecretHeader((string) $key)
                ? '[redacted]'
                : $value;
        }

        return [
            'method' => $request['method'] ?? 'GET',
            'url' => $request['url'] ?? '',
            'query' => $request['query'] ?? [],
            'headers' => $headers,
            'body' => $request['body'] ?? null,
        ];
    }

    private function isSecretHeader(string $name): bool
    {
        $lower = strtolower($name);

        return str_contains($lower, 'authorization')
            || str_contains($lower, 'token')
            || str_contains($lower, 'secret')
            || str_contains($lower, 'password')
            || str_contains($lower, 'api-key')
            || str_contains($lower, 'apikey')
            || $lower === 'cookie';
    }
}
