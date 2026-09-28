<?php

namespace Agentic\Context;

/**
 * Shrinks LLM input (history, tool dumps, RAG, memory) without dropping tools.
 */
final class LlmInputCompactor
{
    /**
     * Keys that add tokens but almost never help the next answer.
     *
     * @var list<string>
     */
    private const NOISE_KEYS = [
        'html',
        'raw_html',
        'css',
        'script',
        'debug',
        'trace',
        'stack',
        'stack_trace',
        'exception',
        'sql',
        'embeddings',
        'embedding',
        'vector',
    ];

    public function __construct(
        public bool $enabled = true,
        public int $historyMessageChars = 700,
        public int $historyRecentChars = 1400,
        public int $historyRecentCount = 2,
        public int $historyTotalChars = 3600,
        public int $toolResultChars = 1800,
        public int $knowledgeChunkChars = 360,
        public int $memoryEntryChars = 180,
        public int $toolDescriptionChars = 160,
        public int $schemaDescriptionChars = 80,
        public int $jsonStringChars = 240,
        public int $jsonListLimit = 12,
        public bool $omitSkillToolLists = true,
    ) {}

    public static function fromConfig(): self
    {
        $cfg = (array) config('agentic.context.compact', []);

        return new self(
            enabled: filter_var($cfg['enabled'] ?? true, FILTER_VALIDATE_BOOL),
            historyMessageChars: max(80, (int) ($cfg['history_message_chars'] ?? 700)),
            historyRecentChars: max(120, (int) ($cfg['history_recent_chars'] ?? 1400)),
            historyRecentCount: max(1, (int) ($cfg['history_recent_count'] ?? 2)),
            historyTotalChars: max(200, (int) ($cfg['history_total_chars'] ?? 3600)),
            toolResultChars: max(200, (int) ($cfg['tool_result_chars'] ?? 1800)),
            knowledgeChunkChars: max(80, (int) ($cfg['knowledge_chunk_chars'] ?? 360)),
            memoryEntryChars: max(40, (int) ($cfg['memory_entry_chars'] ?? 180)),
            toolDescriptionChars: max(40, (int) ($cfg['tool_description_chars'] ?? 160)),
            schemaDescriptionChars: max(20, (int) ($cfg['schema_description_chars'] ?? 80)),
            jsonStringChars: max(40, (int) ($cfg['json_string_chars'] ?? 240)),
            jsonListLimit: max(3, (int) ($cfg['json_list_limit'] ?? 12)),
            omitSkillToolLists: filter_var($cfg['omit_skill_tool_lists'] ?? true, FILTER_VALIDATE_BOOL),
        );
    }

    public function clip(string $text, int $max): string
    {
        $text = trim($text);

        if ($max < 1 || mb_strlen($text) <= $max) {
            return $text;
        }

        $keep = max(1, $max - 12);

        return rtrim(mb_substr($text, 0, $keep)).' …[trimmed]';
    }

    /**
     * @param  list<string>  $texts
     * @return list<string>
     */
    public function history(array $texts): array
    {
        if ($texts === [] || ! $this->enabled) {
            return $texts;
        }

        $count = count($texts);
        $clipped = [];

        foreach ($texts as $index => $text) {
            $budget = $index >= $count - $this->historyRecentCount
                ? $this->historyRecentChars
                : $this->historyMessageChars;
            $clipped[] = $this->clip((string) $text, $budget);
        }

        while (count($clipped) > 1 && $this->totalChars($clipped) > $this->historyTotalChars) {
            array_shift($clipped);
        }

        return $clipped;
    }

    public function toolResult(bool $success, mixed $data, ?string $error): string
    {
        if (! $success) {
            $message = $error ?? 'Tool execution failed.';

            return 'ERROR: '.($this->enabled ? $this->clip($message, $this->toolResultChars) : $message);
        }

        if (is_string($data)) {
            return 'FOUND: '.($this->enabled ? $this->clip($data, $this->toolResultChars) : $data);
        }

        if ($data === null) {
            return 'FOUND: ok';
        }

        $payload = $this->enabled ? $this->compactPayload($data) : $data;
        $json = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return 'FOUND: '.($this->enabled ? $this->clip($json, $this->toolResultChars) : $json);
    }

    public function toolDescription(string $description): string
    {
        return $this->enabled ? $this->clip($description, $this->toolDescriptionChars) : $description;
    }

    public function schemaDescription(string $description): string
    {
        return $this->enabled ? $this->clip($description, $this->schemaDescriptionChars) : $description;
    }

    /**
     * @param  list<array{name?: string, description?: string, tools?: list<string>}>  $skills
     */
    public function skillBlock(array $skills): string
    {
        if ($skills === []) {
            return '';
        }

        $lines = [];

        foreach ($skills as $skill) {
            $name = trim((string) ($skill['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $description = trim((string) ($skill['description'] ?? ''));
            $line = $description === '' ? "- {$name}" : "- {$name}: {$description}";

            if (! $this->omitSkillToolLists) {
                $tools = $skill['tools'] ?? [];
                if (is_array($tools) && $tools !== []) {
                    $line .= ' (tools: '.implode(', ', array_map('strval', $tools)).')';
                }
            }

            $lines[] = $line;
        }

        return $lines === [] ? '' : "Skills:\n".implode("\n", $lines);
    }

    /**
     * @param  list<mixed>  $chunks
     */
    public function knowledgeBlock(array $chunks): string
    {
        $lines = [];

        foreach ($chunks as $chunk) {
            if (is_string($chunk) && trim($chunk) !== '') {
                $lines[] = '- '.$this->maybeClip(trim($chunk), $this->knowledgeChunkChars);

                continue;
            }

            if (! is_array($chunk)) {
                continue;
            }

            $content = trim((string) ($chunk['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $source = trim((string) ($chunk['source'] ?? ''));
            $line = $source !== '' ? $source.': '.$content : $content;
            $lines[] = '- '.$this->maybeClip($line, $this->knowledgeChunkChars);
        }

        return $lines === [] ? '' : "Knowledge:\n".implode("\n", $lines);
    }

    /**
     * @param  list<mixed>  $entries
     */
    public function memoryBlock(array $entries): string
    {
        $lines = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $content = trim((string) ($entry['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $key = trim((string) ($entry['key'] ?? ''));
            $line = $key !== '' ? $key.': '.$content : $content;
            $lines[] = '- '.$this->maybeClip($line, $this->memoryEntryChars);
        }

        return $lines === [] ? '' : "Memory:\n".implode("\n", $lines);
    }

    public function compactPayload(mixed $data, int $depth = 0): mixed
    {
        if ($depth > 6) {
            return '…';
        }

        if (is_string($data)) {
            return $this->clip($data, $this->jsonStringChars);
        }

        if (! is_array($data)) {
            return $data;
        }

        $isList = array_is_list($data);
        $out = [];
        $kept = 0;

        foreach ($data as $key => $value) {
            if ($isList && $kept >= $this->jsonListLimit) {
                $out[] = '…';

                break;
            }

            if (! $isList && is_string($key) && in_array(strtolower($key), self::NOISE_KEYS, true)) {
                continue;
            }

            $compacted = $this->compactPayload($value, $depth + 1);

            if ($compacted === null || $compacted === '' || $compacted === []) {
                continue;
            }

            if ($isList) {
                $out[] = $compacted;
            } else {
                $out[$key] = $compacted;
            }

            $kept++;
        }

        return $out;
    }

    private function maybeClip(string $text, int $max): string
    {
        return $this->enabled ? $this->clip($text, $max) : $text;
    }

    /**
     * @param  list<string>  $texts
     */
    private function totalChars(array $texts): int
    {
        $total = 0;

        foreach ($texts as $text) {
            $total += mb_strlen($text);
        }

        return $total;
    }
}
