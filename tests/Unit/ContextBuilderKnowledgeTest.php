<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Context\ContextBuilder;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Knowledge\Retrievers\ArrayKnowledgeRetriever;
use Agentic\Persistence\InMemory\InMemoryKnowledgeRepository;
use Agentic\Skill\SkillResolver;
use Agentic\Skill\SkillRegistry;
use Agentic\Tests\TestCase;
use Agentic\Tool\Registry\ToolRegistry;

final class ContextBuilderKnowledgeTest extends TestCase
{
    public function test_merges_inline_and_retrieved_knowledge(): void
    {
        $repository = new InMemoryKnowledgeRepository();
        $repository->seed(new KnowledgeSourceDefinition(
            slug: 'shipping',
            name: 'Shipping',
            driver: 'array',
            configuration: [
                'documents' => ['Express shipping arrives in 2 days.'],
            ],
        ));

        $orchestrator = new KnowledgeOrchestrator($repository);
        $orchestrator->extendRetriever(new ArrayKnowledgeRetriever());

        $builder = new ContextBuilder(
            new ToolRegistry(),
            new SkillResolver(new SkillRegistry(), new ToolRegistry()),
            $orchestrator,
        );

        $context = $builder->build(
            new AgentDefinition(
                name: 'support',
                knowledge: [
                    ['content' => 'Always be polite.'],
                    'shipping',
                ],
            ),
            'express delivery time',
        );

        $this->assertCount(2, $context['knowledge']);
        $this->assertSame('Always be polite.', $context['knowledge'][0]['content']);
        $this->assertStringContainsString('Express shipping', $context['knowledge'][1]['content']);
    }
}
