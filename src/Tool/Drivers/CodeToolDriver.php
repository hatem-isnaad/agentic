<?php

namespace Agentic\Tool\Drivers;

use Agentic\Exceptions\InvalidToolInputException;
use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Tool\Contracts\CodeToolHandler;
use Agentic\Tool\Contracts\ToolContract;
use Agentic\Tool\Contracts\ToolDriver;
use Agentic\Tool\Handlers\HandlerRegistry;
use Agentic\Tool\ToolExecutionContext;
use Agentic\Tool\ToolResult;
use Closure;
use Throwable;

/**
 * Executes registered application handlers.
 *
 * Security: never evaluates arbitrary PHP from untrusted configuration.
 * Configuration may only reference a registered handler name.
 */
final class CodeToolDriver implements ToolDriver
{
    public function __construct(
        private HandlerRegistry $handlers,
    ) {}

    public function name(): string
    {
        return 'code';
    }

    public function execute(ToolContract $tool, ToolExecutionContext $context): ToolResult
    {
        $definition = $tool->definition();

        try {
            $this->validateInput($definition->name, $definition->inputSchema, $context->arguments);

            $handlerName = $definition->configuration['handler']
                ?? $definition->configuration['implementation']
                ?? null;

            if (! is_string($handlerName) || $handlerName === '') {
                throw new InvalidToolInputException(
                    $definition->name,
                    'Code tools require a registered handler name in configuration.handler.',
                );
            }

            // Reject raw PHP / file paths / class instantiation from untrusted config.
            if ($this->looksLikeUntrustedSource($handlerName)) {
                return ToolResult::failure(
                    "Code tool [{$definition->name}] refused untrusted handler reference."
                );
            }

            $handler = $this->handlers->resolve($handlerName);
            $result = $this->invoke($handler, $context);

            return $this->normalize($result);
        } catch (ToolNotFoundException|InvalidToolInputException $exception) {
            return ToolResult::failure($exception->getMessage());
        } catch (Throwable $exception) {
            return ToolResult::failure(
                "Code tool [{$definition->name}] execution failed: {$exception->getMessage()}"
            );
        }
    }

    private function invoke(CodeToolHandler|Closure $handler, ToolExecutionContext $context): mixed
    {
        if ($handler instanceof CodeToolHandler) {
            return $handler->handle($context);
        }

        return $handler($context);
    }

    private function normalize(mixed $result): ToolResult
    {
        if ($result instanceof ToolResult) {
            return $result;
        }

        return ToolResult::success($result);
    }

    private function looksLikeUntrustedSource(string $handler): bool
    {
        if (str_contains($handler, '<?php') || str_contains($handler, "\n")) {
            return true;
        }

        if (str_contains($handler, '://') || str_starts_with($handler, '/')) {
            return true;
        }

        // Allow registered aliases and simple Namespaced\Class strings only when registered.
        return false;
    }

    /**
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
