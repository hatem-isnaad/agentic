<?php

namespace Agentic\Tests\Unit;

use Agentic\Filament\AgenticPlugin;
use Agentic\Tests\TestCase;

final class FilamentPluginTest extends TestCase
{
    public function test_plugin_is_available_when_filament_is_installed(): void
    {
        if (! class_exists(\Filament\Panel::class)) {
            $this->markTestSkipped('Filament is not installed.');
        }

        $plugin = AgenticPlugin::make();

        $this->assertSame('agentic', $plugin->getId());
    }
}
