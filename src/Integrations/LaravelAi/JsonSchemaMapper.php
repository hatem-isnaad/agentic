<?php

namespace Agentic\Integrations\LaravelAi;

use Agentic\Context\LlmInputCompactor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * Maps Agentic array input schemas onto Laravel JsonSchema Types.
 *
 * Supported shapes:
 * - ['order_id' => ['type' => 'string', 'required' => true]]
 * - ['properties' => [...], 'required' => ['order_id']]
 */
final class JsonSchemaMapper
{
    /**
     * @param  array<string, mixed>  $inputSchema
     * @return array<string, Type>
     */
    public static function map(JsonSchema $schema, array $inputSchema): array
    {
        if ($inputSchema === []) {
            return [];
        }

        $properties = $inputSchema['properties'] ?? $inputSchema;
        $required = $inputSchema['required'] ?? [];

        if (! is_array($properties)) {
            return [];
        }

        $mapped = [];

        foreach ($properties as $name => $definition) {
            if (! is_string($name)) {
                continue;
            }

            if ($definition instanceof Type) {
                $mapped[$name] = $definition;

                continue;
            }

            if (! is_array($definition)) {
                continue;
            }

            $type = self::typeFor($schema, $definition['type'] ?? 'string');

            if (isset($definition['description']) && is_string($definition['description'])) {
                $type = $type->description(
                    LlmInputCompactor::fromConfig()->schemaDescription($definition['description']),
                );
            }

            $isRequired = ($definition['required'] ?? false) === true
                || in_array($name, $required, true);

            if ($isRequired) {
                $type = $type->required();
            }

            $mapped[$name] = $type;
        }

        return $mapped;
    }

    private static function typeFor(JsonSchema $schema, string $type): Type
    {
        return match (strtolower($type)) {
            'integer', 'int' => $schema->integer(),
            'number', 'float', 'double' => $schema->number(),
            'boolean', 'bool' => $schema->boolean(),
            'array' => $schema->array(),
            'object' => $schema->object(),
            default => $schema->string(),
        };
    }
}
