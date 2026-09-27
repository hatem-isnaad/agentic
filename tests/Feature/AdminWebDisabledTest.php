<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AdminWebDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_blade_admin_is_off_while_json_admin_stays_available(): void
    {
        $this->get('/agentic/admin')->assertNotFound();

        $this->getJson('/api/agentic/admin/dashboard')->assertOk();
    }
}
