<?php

namespace Agentic\Widget\Reply;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Turns model Markdown into safe HTML for the web chat widget.
 */
final class MarkdownHtmlConverter
{
    private static mixed $engine = false;

    public function convert(string $markdown): string
    {
        $markdown = $this->normalizeTableRows(trim($markdown));
        if ($markdown === '') {
            return '';
        }

        $engine = $this->engine();

        return $engine !== null ? trim((string) $engine->convert($markdown)) : $this->fallback($markdown);
    }

    /**
     * Models often insert a blank line between every table row, which breaks GFM tables.
     */
    private function normalizeTableRows(string $markdown): string
    {
        return preg_replace('/(\|[^\n]*\|)[ \t]*\n(?:[ \t]*\n)+(?=\|)/u', "$1\n", $markdown) ?? $markdown;
    }

    private function engine(): mixed
    {
        if (self::$engine !== false) {
            return self::$engine;
        }

        self::$engine = class_exists(GithubFlavoredMarkdownConverter::class)
            ? new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 32,
            ])
            : null;

        return self::$engine;
    }

    public function convertInline(string $markdown): string
    {
        $html = $this->convert($markdown);
        $html = preg_replace('/^<p>(.*)<\/p>$/s', '$1', $html) ?? $html;

        return trim($html);
    }

    private function fallback(string $markdown): string
    {
        $escaped = htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaped = $this->fencedCode($escaped);
        $escaped = $this->tables($escaped);
        $escaped = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $escaped) ?? $escaped;
        $escaped = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $escaped) ?? $escaped;
        $escaped = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $escaped) ?? $escaped;
        $escaped = $this->lists($escaped);
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $escaped) ?? $escaped;
        $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped) ?? $escaped;
        $escaped = preg_replace("/\n{2,}/", '</p><p>', $escaped) ?? $escaped;
        $escaped = nl2br($escaped, false);

        if (! str_contains($escaped, '<p>') && ! str_contains($escaped, '<h') && ! str_contains($escaped, '<ul') && ! str_contains($escaped, '<ol') && ! str_contains($escaped, '<table') && ! str_contains($escaped, '<pre')) {
            return '<p>'.$escaped.'</p>';
        }

        return $escaped;
    }

    private function fencedCode(string $value): string
    {
        return preg_replace_callback(
            '/```([a-zA-Z0-9_-]*)\n(.*?)```/s',
            function (array $matches): string {
                $lang = $matches[1] !== '' ? ' language-'.$matches[1] : '';

                return '<pre><code class="'.$lang.'">'.$matches[2].'</code></pre>';
            },
            $value,
        ) ?? $value;
    }

    private function tables(string $value): string
    {
        return preg_replace_callback(
            '/(?:^|\n)(\|.+\|(?:\n\|[-:| ]+\|)+(?:\n\|.+\|)+)/',
            function (array $matches): string {
                $lines = array_values(array_filter(array_map('trim', explode("\n", trim($matches[1])))));
                if (count($lines) < 2) {
                    return $matches[0];
                }

                $headers = $this->tableCells(array_shift($lines) ?? '');
                array_shift($lines);
                $html = '<table><thead><tr>';
                foreach ($headers as $header) {
                    $html .= '<th>'.$header.'</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ($lines as $line) {
                    $html .= '<tr>';
                    foreach ($this->tableCells($line) as $cell) {
                        $html .= '<td>'.$cell.'</td>';
                    }
                    $html .= '</tr>';
                }

                return "\n".$html.'</tbody></table>';
            },
            $value,
        ) ?? $value;
    }

    /**
     * @return list<string>
     */
    private function tableCells(string $line): array
    {
        $line = trim($line);
        $line = trim($line, '|');

        return array_map('trim', explode('|', $line));
    }

    private function lists(string $value): string
    {
        $value = preg_replace(
            '/(?:^|\n)((?:[-*+] .+(?:\n|$))+)/',
            "\n<ul>\n$1</ul>\n",
            $value,
        ) ?? $value;
        $value = preg_replace('/(?:^|\n)[-*+] (.+)/', '<li>$1</li>', $value) ?? $value;
        $value = preg_replace(
            '/(?:^|\n)((?:\d+\. .+(?:\n|$))+)/',
            "\n<ol>\n$1</ol>\n",
            $value,
        ) ?? $value;

        return preg_replace('/(?:^|\n)\d+\. (.+)/', '<li>$1</li>', $value) ?? $value;
    }
}
