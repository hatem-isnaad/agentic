<?php

namespace Agentic\Tests\Unit;

use Agentic\Context\LlmInputCompactor;
use Agentic\Context\LlmInstructionComposer;
use Agentic\Tests\TestCase;

final class LlmInputCompactorTest extends TestCase
{
    public function test_clips_long_text_and_keeps_short_text(): void
    {
        $compactor = new LlmInputCompactor;

        $this->assertSame('hello', $compactor->clip('hello', 20));
        $this->assertStringEndsWith(' …[trimmed]', $compactor->clip(str_repeat('a', 80), 40));
        $this->assertLessThanOrEqual(40, mb_strlen($compactor->clip(str_repeat('a', 80), 40)));
    }

    public function test_history_keeps_recent_turns_and_drops_oldest_when_over_budget(): void
    {
        $compactor = new LlmInputCompactor(
            historyMessageChars: 20,
            historyRecentChars: 40,
            historyRecentCount: 2,
            historyTotalChars: 50,
        );

        $history = $compactor->history([
            str_repeat('old-', 20),
            'mid turn',
            'latest user',
            'latest assistant with extra detail',
        ]);

        $this->assertNotSame('old-old-old-old-old-old-old-old-old-old-old-old-old-old-old-old-old-old-old-old-', $history[0] ?? null);
        $this->assertStringContainsString('latest', implode(' ', $history));
        $this->assertLessThanOrEqual(50 + 20, mb_strlen(implode('', $history)));
    }

    public function test_tool_result_drops_noise_keys_and_caps_size(): void
    {
        $compactor = new LlmInputCompactor(toolResultChars: 220, jsonStringChars: 40, jsonListLimit: 3);

        $out = $compactor->toolResult(true, [
            'id' => 4,
            'status' => 'pending',
            'html' => str_repeat('<div>ignore</div>', 40),
            'debug' => ['sql' => 'select 1'],
            'items' => [
                ['sku' => 'A', 'name' => 'One'],
                ['sku' => 'B', 'name' => 'Two'],
                ['sku' => 'C', 'name' => 'Three'],
                ['sku' => 'D', 'name' => 'Four'],
            ],
            'empty' => null,
        ], null);

        $this->assertStringStartsWith('FOUND: ', $out);
        $this->assertStringNotContainsString('ignore', $out);
        $this->assertStringNotContainsString('select 1', $out);
        $this->assertStringContainsString('pending', $out);
        $this->assertLessThanOrEqual(220 + 5, mb_strlen($out));
    }

    public function test_tool_error_is_prefixed_and_clipped(): void
    {
        $compactor = new LlmInputCompactor(toolResultChars: 40);

        $out = $compactor->toolResult(false, null, str_repeat('404 page html ', 30));

        $this->assertStringStartsWith('ERROR: ', $out);
        $this->assertStringContainsString('[trimmed]', $out);
    }

    public function test_instruction_composer_uses_text_not_json(): void
    {
        $composer = new LlmInstructionComposer(new LlmInputCompactor);

        $prompt = $composer->compose([
            'instructions' => 'Help users.',
            'skills' => [
                ['name' => 'orders', 'description' => 'Lookup orders', 'tools' => ['orders-get', 'orders-list']],
            ],
            'knowledge' => [
                ['content' => 'Returns in 30 days.', 'source' => 'policy', 'score' => 0.91, 'metadata' => ['chunk' => 1]],
            ],
            'memory' => [
                ['key' => 'store', 'content' => 'GENTO'],
            ],
        ]);

        $this->assertStringContainsString('Help users.', $prompt);
        $this->assertStringContainsString("Skills:\n- orders: Lookup orders", $prompt);
        $this->assertStringNotContainsString('orders-get', $prompt);
        $this->assertStringContainsString('policy: Returns in 30 days.', $prompt);
        $this->assertStringNotContainsString('"score"', $prompt);
        $this->assertStringContainsString('store: GENTO', $prompt);
    }

    public function test_disabled_compactor_keeps_full_tool_result(): void
    {
        $compactor = new LlmInputCompactor(enabled: false);
        $payload = ['html' => str_repeat('x', 50), 'id' => 1];

        $out = $compactor->toolResult(true, $payload, null);

        $this->assertSame('FOUND: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $out);
    }
}
