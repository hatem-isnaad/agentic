<?php

namespace Agentic\Widget\Reply;

/**
 * Normalizes assistant payloads to HTML for widget clients.
 */
final class HtmlReplyRenderer
{
    public function __construct(
        private MarkdownHtmlConverter $markdown = new MarkdownHtmlConverter(),
    ) {}

    /**
     * @param  array<string, mixed>|string|null  $payload
     */
    public function render(array|string|null $payload, string $format = 'text'): string
    {
        if (is_string($payload)) {
            return $this->text($payload);
        }

        if ($payload === null) {
            return '';
        }

        $format = $payload['format'] ?? $format;

        if ($format === 'blocks' && is_array($payload['blocks'] ?? null)) {
            return $this->renderBlocks($payload['blocks']);
        }

        return match ($format) {
            'html' => $this->safeHtml((string) ($payload['html'] ?? $payload['content'] ?? '')),
            'table' => $this->table(is_array($payload['rows'] ?? null) ? $payload['rows'] : []),
            'list' => $this->list(is_array($payload['items'] ?? null) ? $payload['items'] : []),
            'card' => $this->card($payload),
            'code' => $this->code($payload),
            default => $this->text((string) ($payload['text'] ?? $payload['content'] ?? json_encode($payload))),
        };
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public function renderBlocks(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            $type = (string) ($block['type'] ?? 'text');

            $html .= match ($type) {
                'html' => $this->safeHtml((string) ($block['html'] ?? '')),
                'table' => $this->table(is_array($block['rows'] ?? null) ? $block['rows'] : []),
                'list' => $this->list(is_array($block['items'] ?? null) ? $block['items'] : []),
                'card' => $this->card($block),
                'code' => $this->code($block),
                'actions' => $this->actions(is_array($block['buttons'] ?? null) ? $block['buttons'] : []),
                default => $this->text((string) ($block['text'] ?? '')),
            };
        }

        return $html;
    }

    private function text(string $value): string
    {
        return $this->markdown->convert($value);
    }

    private function safeHtml(string $html): string
    {
        $html = preg_replace('/<(script|style|iframe|object|embed|form)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        return preg_replace('/javascript\s*:/i', '', $html) ?? $html;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function table(array $rows): string
    {
        if ($rows === []) {
            return '<p></p>';
        }

        $headers = array_keys($rows[0]);
        $html = '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>'.$this->markdown->convertInline((string) $header).'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<td>'.$this->markdown->convertInline((string) ($row[$header] ?? '')).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * @param  list<mixed>  $items
     */
    private function list(array $items): string
    {
        if ($items === []) {
            return '<ul></ul>';
        }

        $html = '<ul>';
        foreach ($items as $item) {
            $html .= '<li>'.$this->markdown->convertInline(is_scalar($item) ? (string) $item : (string) json_encode($item)).'</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function card(array $payload): string
    {
        $title = $this->markdown->convertInline((string) ($payload['title'] ?? ''));
        $body = $this->markdown->convert((string) ($payload['body'] ?? $payload['text'] ?? ''));
        $footer = $this->markdown->convertInline((string) ($payload['footer'] ?? ''));

        $html = '<article class="agentic-card">';
        if ($title !== '') {
            $html .= '<header><strong>'.$title.'</strong></header>';
        }
        if ($body !== '') {
            $html .= $body;
        }
        if ($footer !== '') {
            $html .= '<footer><small>'.$footer.'</small></footer>';
        }
        $html .= '</article>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function code(array $payload): string
    {
        $language = e((string) ($payload['language'] ?? 'text'));
        $content = e((string) ($payload['code'] ?? $payload['content'] ?? ''));

        return '<pre><code class="language-'.$language.'">'.$content.'</code></pre>';
    }

    /**
     * @param  list<array<string, mixed>>  $buttons
     */
    private function actions(array $buttons): string
    {
        if ($buttons === []) {
            return '';
        }

        $html = '<div class="agentic-actions" role="group">';

        foreach ($buttons as $button) {
            $label = e((string) ($button['label'] ?? 'Action'));
            $action = e((string) ($button['action'] ?? 'custom'));
            $style = e((string) ($button['style'] ?? 'default'));
            $payload = e(json_encode(is_array($button['payload'] ?? null) ? $button['payload'] : [], JSON_THROW_ON_ERROR));

            $html .= '<button type="button" class="agentic-action agentic-action-'.$style.'" data-action="'.$action.'" data-payload="'.$payload.'">'.$label.'</button>';
        }

        $html .= '</div>';

        return $html;
    }
}
