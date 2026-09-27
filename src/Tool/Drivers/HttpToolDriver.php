<?php

namespace Agentic\Tool\Drivers;

use Agentic\Exceptions\InvalidToolInputException;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Contracts\ToolDriver;
use Agentic\Tool\Drivers\Http\HttpRequestBuilder;
use Agentic\Tool\Drivers\Http\ResponseMapper;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Executes HTTP tools against external APIs.
 *
 * Flow: Tool → RequestBuilder → Auth → HTTP → ResponseMapper → ToolResult
 */
final class HttpToolDriver implements ToolDriver
{
    public function __construct(
        private HttpRequestBuilder $requests = new HttpRequestBuilder(),
        private ResponseMapper $responses = new ResponseMapper(),
    ) {}

    public function name(): string
    {
        return 'http';
    }

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        $definition = $tool->definition();

        try {
            $this->validateInput($definition->name, $definition->inputSchema, $context->arguments);

            $request = $this->requests->build($definition, $context);
            $response = $this->send($request);

            if ($response->failed()) {
                return ToolResult::failure($this->formatHttpError(
                    $definition->name,
                    $response,
                    is_array($definition->configuration['error_mapping'] ?? null)
                        ? $definition->configuration['error_mapping']
                        : [],
                ));
            }

            $mapping = is_array($definition->configuration['response_mapping'] ?? null)
                ? $definition->configuration['response_mapping']
                : [];

            $data = $this->responses->map($response, $mapping);

            if ($definition->outputSchema !== []) {
                // Soft validation: ensure mapped payload is an array when schema is present.
                if (! is_array($data)) {
                    return ToolResult::failure(
                        "HTTP tool [{$definition->name}] output did not match the configured output schema shape."
                    );
                }
            }

            return ToolResult::success($data);
        } catch (InvalidToolInputException $exception) {
            return ToolResult::failure($exception->getMessage());
        } catch (ConnectionException $exception) {
            return ToolResult::failure(
                "HTTP tool [{$definition->name}] connection failed: {$exception->getMessage()}"
            );
        } catch (Throwable $exception) {
            return ToolResult::failure(
                "HTTP tool [{$definition->name}] execution failed: {$exception->getMessage()}"
            );
        }
    }

    /**
     * @param  array{
     *     method: string,
     *     url: string,
     *     headers: array<string, string>,
     *     query: array<string, mixed>,
     *     body: mixed,
     *     timeout: float|int,
     *     retry: array{times: int, sleep: int, statuses: list<int>, unsafe_methods: bool}
     *  }  $request
     */
    private function send(array $request): \\Illuminate\\Http\\Client\\Response
    {
        /** @var PendingRequest $pending */
        $pending = Http::withHeaders($request['headers'])
            ->timeout((float) $request['timeout'])
            ->acceptJson();

        $method = strtolower($request['method']);
        $url = $request['url'];
        $query = $request['query'];
        $body = $request['body'];
        $retry = $request['retry'];
        $attempts = $retry['times'] + 1;
        $retryableMethod = in_array($method, ['get', 'head', 'delete', 'put', 'options'], true)
            || $retry['unsafe_methods'];

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->sendOnce($pending, $method, $url, $query, $body);

                if (
                    $attempt < $attempts
                    && $retryableMethod
                    && in_array($response->status(), $retry['statuses'], true)
                ) {
                    usleep($retry['sleep'] * 1000 * $attempt);
                    continue;
                }

                return $response;
            } catch (ConnectionException $exception) {
                if ($attempt >= $attempts || ! $retryableMethod) {
                    throw $exception;
                }

                usleep($retry['sleep'] * 1000 * $attempt);
            }
        }

        throw new ConnectionException('HTTP request retry policy exhausted.');
    }

    private function sendOnce(
        PendingRequest $pending,
        string $method,
        string $url,
        array $query,
        mixed $body,
    ): \\Illuminate\\Http\\Client\\Response {
        if (in_array($method, ['get', 'head', 'delete'], true)) {
            return $pending->withQueryParameters($query)->{$method}($url);
        }

        if (is_array($body)) {
            return $pending->withQueryParameters($query)->asJson()->{$method}($url, $body);
        }

        if (is_string($body) && $body !== '') {
            return $pending->withQueryParameters($query)
                ->withBody($body, 'application/json')
                ->{$method}($url);
        }

        return $pending->withQueryParameters($query)->{$method}($url);
    }

    private function formatHttpError(string $tool, \\Illuminate\\Http\\Client\\Response $response, array $mapping): string
    {
        $payload = $response->json() ?? $response->body();
        $details = $mapping === [] ? $payload : (new ResponseMapper())->map($response, $mapping);

        $encoded = is_string($details) ? $details : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return sprintf(
            'HTTP tool [%s] failed with status %d: %s',
            $tool,
            $response->status(),
            $encoded ?: 'Unknown HTTP error.',
        );
    }

    /**
     * Minimal required-field validation against input schema properties.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $arguments
     */
    private function validateInput(string $tool, array $schema, array $arguments): void
    {
        if ($schema === []) {
            return;
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : $schema;
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

        foreach ($properties as $name => $definition) {
            if (! is_string($name)) {
                continue;
            }

            $isRequired = in_array($name, $required, true)
                || (is_array($definition) && ($definition['required'] ?? false) === true);

            if ($isRequired && ! array_key_exists($name, $arguments)) {
                throw new InvalidToolInputException($tool, "Missing required argument [{$name}].");
            }
        }
    }
}
