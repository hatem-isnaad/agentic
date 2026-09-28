<?php

namespace Agentic\Tests\Feature;

use Agentic\Models\Evaluation;
use Agentic\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class EvaluationAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_score_a_conversation(): void
    {
        $this->postJson('/api/agentic/admin/evaluations', [
            'conversation_id' => 'conv-1',
            'agent_slug' => 'support',
            'score' => 4,
            'label' => 'helpful',
            'notes' => 'Clear answer',
        ])->assertCreated()->assertJsonPath('data.score', 4);

        $this->assertSame(1, Evaluation::query()->count());
        $this->getJson('/api/agentic/admin/evaluations')->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_score_must_be_one_to_five(): void
    {
        $this->postJson('/api/agentic/admin/evaluations', [
            'score' => 8,
        ])->assertStatus(422);
    }
}
