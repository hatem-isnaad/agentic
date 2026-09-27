<?php

namespace Agentic\Tests\Feature;

use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_agent_crud_routes_render(): void
    {
        $this->get('/agentic/admin')
            ->assertOk()
            ->assertSee('Agentic Admin (placeholder)', false);

        $this->get('/agentic/admin/agents')
            ->assertOk()
            ->assertSee('Create agent', false);

        $this->post('/agentic/admin/agents', [
            'name' => 'Support',
            'slug' => 'support',
            'description' => 'Help desk agent',
            'status' => 'draft',
        ])->assertRedirect(route('agentic.admin.agents.show', 'support'));

        $this->get('/agentic/admin/agents/support')
            ->assertOk()
            ->assertSee('support', false);

        $this->put('/agentic/admin/agents/support', [
            'name' => 'Support v2',
            'slug' => 'support',
            'description' => 'Updated',
            'status' => 'published',
        ])->assertRedirect(route('agentic.admin.agents.show', 'support'));

        $this->delete('/agentic/admin/agents/support')
            ->assertRedirect(route('agentic.admin.agents.index'));
    }

}
