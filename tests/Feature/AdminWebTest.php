<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AdminWebTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', true);
        $app['config']->set('agentic.admin.web.enabled', true);
        $app['config']->set('agentic.admin.web.ui', 'spa');
        $app['config']->set('agentic.widget.web.enabled', true);
    }

    public function test_admin_dashboard_renders(): void
    {
        $prefix = trim((string) config('agentic.admin.web.prefix'), '/');

        $this->get('/'.$prefix)
            ->assertOk()
            ->assertSee('id="agentic-admin-root"', false);
    }

    public function test_widget_chat_page_renders(): void
    {
        $prefix = trim((string) config('agentic.widget.web.prefix'), '/');

        $this->get('/'.$prefix.'?agent=support')
            ->assertOk()
            ->assertSee('support', false);
    }

    public function test_spa_deep_links_serve_shell(): void
    {
        $admin = trim((string) config('agentic.admin.web.prefix'), '/');

        $this->get('/'.$admin.'/agents')->assertOk()->assertSee('agentic-admin-root', false);
        $this->get('/'.$admin.'/workflows')->assertOk()->assertSee('agentic-admin-root', false);
    }
}
