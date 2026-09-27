<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;

final class AdminAiRegistryApiTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.api.enabled', true);
    }

    public function test_ai_registry_lists_configured_providers(): void
    {
        $this->getJson('/api/agentic/admin/ai-registry')
            ->assertOk()
            ->assertJsonPath('data.default_provider', config('agentic.ai.provider'))
            ->assertJsonFragment(['key' => 'gemini'])
            ->assertJsonFragment(['key' => 'ollama'])
            ->assertJsonPath('data.persona.dialects.1', 'saudi');
    }
}
