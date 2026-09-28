<?php

namespace Agentic\Widget\Reply;

/**
 * Normalizes assistant output into block JSON for headless widget clients.
 */
final class StructuredReplyBuilder
{
    public function __construct(private HtmlReplyRenderer $html) {}

    /**
     * @param  array<string, mixed>|string|null  $payload
     * @return array{format: string, blocks: list<array<string, mixed>>, html: string}
     */
    public function build(array|string|null $payload): array
    {
        if (is_string($payload)) {
            $blocks = [['type' => 'text', 'text' => $payload]];

            return [
                'format' => 'blocks',
                'blocks' => $blocks,
                'html' => $this->html->render($payload),
            ];
        }

        if ($payload === null) {
            return ['format' => 'blocks', 'blocks' => [], 'html' => ''];
        }

        if (($payload['format'] ?? '') === 'blocks' && is_array($payload['blocks'] ?? null)) {
            $blocks = $this->normalizeBlocks($payload['blocks']);

            return [
                'format' => 'blocks',
                'blocks' => $blocks,
                'html' => $this->html->renderBlocks($blocks),
            ];
        }

        $blocks = [$this->legacyBlock($payload)];

        return [
            'format' => 'blocks',
            'blocks' => $blocks,
            'html' => $this->html->render($payload),
        ];
    }

    /**
     * @param  list<mixed>  $blocks
     * @return list<array<string, mixed>>
     */
    private function normalizeBlocks(array $blocks): array
    {
        $normalized = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? 'text');

            $normalized[] = match ($type) {
                'text' => ['type' => 'text', 'text' => (string) ($block['text'] ?? '')],
                'html' => ['type' => 'html', 'html' => (string) ($block['html'] ?? '')],
                'table' => ['type' => 'table', 'rows' => is_array($block['rows'] ?? null) ? $block['rows'] : []],
                'list' => ['type' => 'list', 'items' => is_array($block['items'] ?? null) ? $block['items'] : []],
                'card' => [
                    'type' => 'card',
                    'variant' => (string) ($block['variant'] ?? ''),
                    'kicker' => (string) ($block['kicker'] ?? ''),
                    'title' => (string) ($block['title'] ?? ''),
                    'body' => (string) ($block['body'] ?? $block['text'] ?? ''),
                    'footer' => (string) ($block['footer'] ?? ''),
                    'buttons' => $this->normalizeButtons(is_array($block['buttons'] ?? null) ? $block['buttons'] : []),
                ],
                'code' => [
                    'type' => 'code',
                    'language' => (string) ($block['language'] ?? 'text'),
                    'code' => (string) ($block['code'] ?? $block['content'] ?? ''),
                ],
                'actions' => [
                    'type' => 'actions',
                    'buttons' => $this->normalizeButtons(is_array($block['buttons'] ?? null) ? $block['buttons'] : []),
                ],
                default => ['type' => 'text', 'text' => json_encode($block)],
            };
        }

        return $normalized;
    }

    /**
     * @param  list<mixed>  $buttons
     * @return list<array<string, mixed>>
     */
    private function normalizeButtons(array $buttons): array
    {
        $normalized = [];

        foreach ($buttons as $button) {
            if (! is_array($button)) {
                continue;
            }

            $normalized[] = [
                'label' => (string) ($button['label'] ?? 'Action'),
                'action' => (string) ($button['action'] ?? 'custom'),
                'style' => (string) ($button['style'] ?? 'default'),
                'payload' => is_array($button['payload'] ?? null) ? $button['payload'] : [],
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function legacyBlock(array $payload): array
    {
        $format = (string) ($payload['format'] ?? 'text');

        return match ($format) {
            'html' => ['type' => 'html', 'html' => (string) ($payload['html'] ?? $payload['content'] ?? '')],
            'table' => ['type' => 'table', 'rows' => is_array($payload['rows'] ?? null) ? $payload['rows'] : []],
            'list' => ['type' => 'list', 'items' => is_array($payload['items'] ?? null) ? $payload['items'] : []],
            'card' => [
                'type' => 'card',
                'title' => (string) ($payload['title'] ?? ''),
                'body' => (string) ($payload['body'] ?? $payload['text'] ?? ''),
                'footer' => (string) ($payload['footer'] ?? ''),
            ],
            'code' => [
                'type' => 'code',
                'language' => (string) ($payload['language'] ?? 'text'),
                'code' => (string) ($payload['code'] ?? $payload['content'] ?? ''),
            ],
            default => ['type' => 'text', 'text' => (string) ($payload['text'] ?? $payload['content'] ?? json_encode($payload))],
        };
    }
}
