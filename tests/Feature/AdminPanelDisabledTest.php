<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;

final class AdminPanelDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agentic.admin.enabled', false);
    }

    public function test_admin_routes_are_not_registered_when_disabled(): void
    {
        $this->get('/agentic/admin')->assertNotFound();
    }
}
