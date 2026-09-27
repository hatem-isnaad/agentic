<?php

namespace Agentic\Tests\Unit;

use Agentic\Skill\LlmSkillRouter;
use Agentic\Skill\SkillDefinition;
use Agentic\Skill\SkillRegistry;
use Agentic\Skill\SkillResolver;
use Agentic\Tool\Registry\ToolRegistry;
use Agentic\Tests\TestCase;
use Laravel\Ai\Classification;
use Laravel\Ai\Responses\Data\ChoiceAnswer;

final class LlmSkillRouterTest extends TestCase
{
    public function test_selects_skill_when_classification_is_confident(): void
    {
        Classification::fake([
            [
                'skill' => new ChoiceAnswer(
                    'billing',
                    ['orders' => 0.05, 'billing' => 0.92, 'support' => 0.03],
                    confidence: 0.92,
                ),
            ],
        ]);

        $skills = new SkillRegistry();
        $skills->register(new SkillDefinition('orders', 'Order and shipment operations.'));
        $skills->register(new SkillDefinition('billing', 'Invoices, payments, and refunds.'));
        $skills->register(new SkillDefinition('support', 'General customer support.'));

        $router = new LlmSkillRouter(
            new SkillResolver($skills, new ToolRegistry()),
        );

        $result = $router->select(
            ['orders', 'billing', 'support'],
            'My invoice is wrong.',
        );

        $this->assertNotNull($result);
        $this->assertSame(['billing'], $result->skills);
        $this->assertSame(['ai'], $result->matches['billing']);

        Classification::assertClassified(
            fn ($prompt) => $prompt->contains('My invoice is wrong.')
                && $prompt->asks('skill'),
        );
    }

    public function test_rejects_low_confidence_classification(): void
    {
        Classification::fake([
            [
                'skill' => new ChoiceAnswer(
                    'billing',
                    ['orders' => 0.30, 'billing' => 0.55, 'support' => 0.15],
                    confidence: 0.55,
                ),
            ],
        ]);

        $skills = new SkillRegistry();
        $skills->register(new SkillDefinition('orders', 'Orders.'));
        $skills->register(new SkillDefinition('billing', 'Billing.'));

        $router = new LlmSkillRouter(
            new SkillResolver($skills, new ToolRegistry()),
        );

        $this->assertNull($router->select(['orders', 'billing'], 'Something unclear.'));

        Classification::assertClassified();
    }
}
