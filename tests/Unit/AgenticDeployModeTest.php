<?php

namespace Agentic\Tests\Unit;

use Agentic\Support\AgenticDeployMode;
use Agentic\Tests\TestCase;

final class AgenticDeployModeTest extends TestCase
{
    public function test_local_mode_opens_admin_api_without_sanctum(): void
    {
        config(['agentic.deploy.mode' => 'local']);
        AgenticDeployMode::applyPresets();

        $this->assertSame('local', AgenticDeployMode::current());
        $this->assertFalse(config('agentic.auth.protect.admin_api'));
        $this->assertTrue(config('agentic.admin.web.enabled'));
    }

    public function test_production_mode_secures_admin_and_embed(): void
    {
        config(['agentic.deploy.mode' => 'production']);
        AgenticDeployMode::applyPresets();

        $this->assertTrue(config('agentic.auth.protect.admin_api'));
        $this->assertTrue(config('agentic.widget.embed.require_token'));
    }

    public function test_widget_mode_disables_admin(): void
    {
        config(['agentic.deploy.mode' => 'widget']);
        AgenticDeployMode::applyPresets();

        $this->assertTrue(AgenticDeployMode::isWidgetOnly());
        $this->assertFalse(config('agentic.admin.enabled'));
        $this->assertTrue(config('agentic.widget.enabled'));
    }
}
