<?php

namespace Agentic\Tests\Feature;

use Agentic\Contracts\Repositories\SkillRepository;
use Agentic\Skill\Routing\Support\SkillKeywordMapBuilder;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class SkillRoutingKeywordsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_skill_crud_persists_routing_keywords_for_runtime(): void
    {
        $this->postJson('/api/agentic/admin/skills', [
            'name' => 'Billing',
            'slug' => 'billing',
            'description' => 'Invoices',
            'status' => 'published',
            'routing_keywords' => ['invoice', 'payment'],
            'tools' => [],
        ])->assertCreated()
            ->assertJsonPath('data.routing_keywords', ['invoice', 'payment']);

        $this->getJson('/api/agentic/admin/skills/routing-schema')
            ->assertOk()
            ->assertJsonPath('data.fields.0.key', 'routing_keywords');

        $skill = app(SkillRepository::class)->findBySlug('billing');
        $this->assertSame(['invoice', 'payment'], $skill?->metadata['routing_keywords']);

        $map = app(SkillKeywordMapBuilder::class)->build(['billing']);
        $this->assertSame(['invoice', 'payment'], $map['billing']);
    }
}
