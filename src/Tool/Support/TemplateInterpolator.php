<?php

namespace Agentic\Tool\Support;

/**
 * Replaces {placeholders} using tool arguments and runtime variables.
 */
final class TemplateInterpolator
{
    /**
     * @param  array<string, mixed>  $values
     */
    public static function string(string $template, array $values): string
    {
        return (string) preg_replace_callback(
            '/\{([a-zA-Z0-9_.]+)\}/',
            function (array $matches) use ($values): string {
                $value = data_get($values, $matches[1]);

                if ($value === null) {
                    return $matches[0];
                }

                if (is_scalar($value)) {
                    return (string) $value;
                }

                return json_encode($value, JSON_THROW_ON_ERROR);
            },
            $template,
        );
    }

    /**
     * Recursively interpolate strings inside arrays.
     *
     * @param  array<mixed>  $payload
     * @param  array<string, mixed>  $values
     * @return array<mixed>
     */
    public static function array(array $payload, array $values): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            $resolvedKey = is_string($key) ? self::string($key, $values) : $key;

            $result[$resolvedKey] = match (true) {
                is_string($value) => self::string($value, $values),
                is_array($value) => self::array($value, $values),
                default => $value,
            };
        }

        return $result;
    }
}
