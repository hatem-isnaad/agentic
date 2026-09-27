<?php

namespace Agentic\Tests\Unit;

use Agentic\Integrations\LaravelAi\AiProviderResolver;
use Agentic\Tests\TestCase;
use RuntimeException;

final class AiProviderResolverTest extends TestCase
{
    public function test_empty_agent_values_fall_back_to_config(): void
    {
        config()->set('agentic.ai.provider', 'ollama');
        config()->set('agentic.ai.model', 'qwen3:8b');

        $this->assertSame('ollama', AiProviderResolver::provider(''));
        $this->assertSame('ollama', AiProviderResolver::provider('   '));
        $this->assertSame('ollama', AiProviderResolver::provider(null));
        $this->assertSame('qwen3:8b', AiProviderResolver::model(null));
        $this->assertSame('qwen3:8b', AiProviderResolver::model(''));
    }

    public function test_explicit_agent_values_win(): void
    {
        config()->set('agentic.ai.provider', 'ollama');
        config()->set('agentic.ai.model', 'qwen3:8b');

        $this->assertSame('anthropic', AiProviderResolver::provider('anthropic'));
        $this->assertSame('claude-sonnet-4-20250514', AiProviderResolver::model('claude-sonnet-4-20250514'));
    }

    public function test_anthropic_without_key_fails_clearly(): void
    {
        config()->set('ai.providers.anthropic.key', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY');

        AiProviderResolver::assertReady('anthropic');
    }

    public function test_anthropic_with_key_is_ready(): void
    {
        config()->set('ai.providers.anthropic.key', 'sk-ant-test');

        AiProviderResolver::assertReady('anthropic');

        $this->assertTrue(true);
    }
}
