<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentPersona;
use Agentic\Agent\AgentPersonaComposer;
use Agentic\Tests\TestCase;

final class AgentPersonaComposerTest extends TestCase
{
    public function test_empty_persona_leaves_instructions_unchanged(): void
    {
        $composed = (new AgentPersonaComposer())->compose('Help users.', new AgentPersona());

        $this->assertSame('Help users.', $composed);
    }

    public function test_saudi_female_persona_is_injected(): void
    {
        $persona = new AgentPersona(
            displayName: 'Sara',
            gender: 'female',
            language: 'ar',
            dialect: 'saudi',
            tone: 'friendly',
        );

        $composed = (new AgentPersonaComposer())->compose('Help with orders.', $persona);

        $this->assertStringContainsString('Help with orders.', $composed);
        $this->assertStringContainsString('Sara', $composed);
        $this->assertStringContainsString('female', $composed);
        $this->assertStringContainsString('Saudi', $composed);
        $this->assertStringContainsString('friendly', $composed);
    }

    public function test_from_config_ignores_unknown_values(): void
    {
        $persona = AgentPersona::fromConfig([
            'display_name' => 'Ahmed',
            'gender' => 'robot',
            'language' => 'ar',
            'dialect' => 'saudi',
        ]);

        $this->assertSame('Ahmed', $persona->displayName);
        $this->assertNull($persona->gender);
        $this->assertSame('ar', $persona->language);
        $this->assertSame('saudi', $persona->dialect);
    }
}
