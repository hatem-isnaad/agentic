<?php

namespace Agentic\Widget\Reply;

/**
 * Strip model "thinking" blocks and normalize assistant text before widget storage.
 */
final class WidgetAssistantOutputSanitizer
{
    public static function clean(string $text): string
    {
        $text = preg_replace('/<think\b[^>]*>.*?<\/think>/is', '', $text) ?? $text;
        $text = preg_replace('/<redacted_reasoning\b[^>]*>.*?<\/redacted_reasoning>/is', '', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param  array<string, mixed>|string|null  $output
     * @return array<string, mixed>|string|null
     */
    public static function cleanOutput(array|string|null $output): array|string|null
    {
        if (is_string($output)) {
            return self::clean($output);
        }

        if (! is_array($output)) {
            return $output;
        }

        if (isset($output['text']) && is_string($output['text'])) {
            $output['text'] = self::clean($output['text']);
        }

        return $output;
    }
}
