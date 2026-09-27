<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Widget\Reply\StructuredReplyBuilder;

final class StructuredReplyBuilderTest extends TestCase
{
    public function test_blocks_and_actions_schema(): void
    {
        $builder = app(StructuredReplyBuilder::class);

        $built = $builder->build([
            'format' => 'blocks',
            'blocks' => [
                ['type' => 'text', 'text' => 'Choose:'],
                [
                    'type' => 'actions',
                    'buttons' => [
                        ['label' => 'Approve', 'action' => 'approve', 'payload' => ['id' => '1']],
                    ],
                ],
            ],
        ]);

        $this->assertSame('blocks', $built['format']);
        $this->assertCount(2, $built['blocks']);
        $this->assertStringContainsString('agentic-actions', $built['html']);
        $this->assertStringContainsString('Approve', $built['html']);
    }
}
