<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Laravel\Ai\Enums\Lab;

final class AiProvidersConfigTest extends TestCase
{
    public function test_agentic_registry_includes_gemini_and_ollama(): void
    {
        $providers = config('agentic.ai.providers');

        $this->assertArrayHasKey('gemini', $providers);
        $this->assertArrayHasKey('ollama', $providers);
        $this->assertNotEmpty($providers['gemini']['models']);
        $this->assertNotEmpty($providers['ollama']['models']);
    }

    public function test_laravel_ai_sdk_registers_gemini_and_ollama_labs(): void
    {
        $this->assertContains(Lab::Gemini, Lab::cases());
        $this->assertContains(Lab::Ollama, Lab::cases());
    }
}
