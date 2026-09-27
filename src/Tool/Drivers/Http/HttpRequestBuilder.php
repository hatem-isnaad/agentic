<?php

namespace Agentic\Tool\Drivers\Http;

use Agentic\Tool\Support\TemplateInterpolator;
use Agentic\Tool\ToolDefinition;
use Agentic\Tool\ToolExecutionContext;
use InvalidArgumentException;

/**
 * Builds a normalized HTTP request from tool configuration + execution arguments.
 */
final class HttpRequestBuilder
{
    public function __construct(
        private AuthenticationResolver $authentication = new AuthenticationResolver(),
    ) {}

    /**
     * @return array{
     *     method: string,
     *     url: string,
     *     headers: array<string, string>,
     *     query: array<string, mixed>,
     *     body: mixed,
     *     timeout: float|int,
     *     retry: array{times: int, sleep: int}
     * }
     */
    public function build(ToolDefinition $definition, ToolExecutionContext $context): array
    {
        $config = $definition->configuration;
        $values = array_merge(
            $context->runtime()->all(),
            $context->execution?->variables ?? [],
            $context->arguments,
        );

        $method = strtoupper((string) ($config['method'] ?? 'GET'));
        $allowed = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'];

        if (! in_array($method, $allowed, true)) {
            throw new InvalidArgumentException("Unsupported HTTP method [{$method}].");
        }

        $url = (string) ($config['url'] ?? '');

        if ($url === '') {
            throw new InvalidArgumentException("HTTP tool [{$definition->name}] is missing url.");
        }

        $url = TemplateInterpolator::string($url, $values);

        $headers = TemplateInterpolator::array(
            $this->stringMap($config['headers'] ?? []),
            $values,
        );

        $query = TemplateInterpolator::array(
            is_array($config['query'] ?? null) ? $config['query'] : [],
            $values,
        );

        $body = $config['body'] ?? null;

        if (is_array($body)) {
            $body = TemplateInterpolator::array($body, $values);
        } elseif (is_string($body)) {
            $body = TemplateInterpolator::string($body, $values);
        }

        // Optional request mapping: merge/override from argument paths.
        $requestMapping = $config['request_mapping'] ?? [];

        if (is_array($requestMapping) && $requestMapping !== []) {
            [$headers, $query, $body] = $this->applyRequestMapping(
                $requestMapping,
                $context->arguments,
                $headers,
                $query,
                $body,
            );
        }

        $auth = $this->authentication->resolve(
            is_array($config['auth'] ?? null) ? $config['auth'] : null
        );

        $headers = array_merge($headers, $auth['headers']);
        $query = array_merge($query, $auth['query']);

        $retry = is_array($config['retry'] ?? null) ? $config['retry'] : [];

        return [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'query' => $query,
            'body' => $body,
            'timeout' => $config['timeout'] ?? 30,
            'retry' => [
                'times' => (int) ($retry['times'] ?? 0),
                'sleep' => (int) ($retry['sleep'] ?? 100),
            ],
        ];
    }

    /**
     * @param  array<mixed>  $headers
     * @return array<string, string>
     */
    private function stringMap(mixed $headers): array
    {
        if (! is_array($headers)) {
            return [];
        }

        $map = [];

        foreach ($headers as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $map[$key] = (string) $value;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @param  array<string, mixed>  $arguments
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @return array{0: array<string, string>, 1: array<string, mixed>, 2: mixed}
     */
    private function applyRequestMapping(
        array $mapping,
        array $arguments,
        array $headers,
        array $query,
        mixed $body,
    ): array {
        foreach ($mapping as $target => $source) {
            if (! is_string($target) || ! is_string($source)) {
                continue;
            }

            $value = data_get($arguments, $source);

            if (str_starts_with($target, 'headers.')) {
                data_set($headers, substr($target, 8), $value);
            } elseif (str_starts_with($target, 'query.')) {
                data_set($query, substr($target, 6), $value);
            } elseif ($target === 'body') {
                $body = $value;
            } elseif (str_starts_with($target, 'body.')) {
                if (! is_array($body)) {
                    $body = [];
                }
                data_set($body, substr($target, 5), $value);
            }
        }

        return [$headers, $query, $body];
    }
}
