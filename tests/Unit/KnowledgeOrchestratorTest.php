<?php

namespace Agentic\Tests\Unit;

use Agentic\Agent\AgentDefinition;
use Agentic\Knowledge\KnowledgeOrchestrator;
use Agentic\Knowledge\KnowledgeSourceDefinition;
use Agentic\Knowledge\Retrievers\ArrayKnowledgeRetriever;
use Agentic\Persistence\InMemory\InMemoryKnowledgeRepository;
use Agentic\Tests\TestCase;

final class KnowledgeOrchestratorTest extends TestCase
{
    public function test_retrieves_from_agent_configured_sources(): void
    {
        $repository = new InMemoryKnowledgeRepository();
        $repository->seed(new KnowledgeSourceDefinition(
            slug: 'policies',
            name: 'Policies',
            driver: 'array',
            configuration: [
                'documents' => ['Returns are accepted within 30 days.'],
            ],
            status: 'published',
        ));

        $orchestrator = new KnowledgeOrchestrator($repository);
        $orchestrator->extendRetriever(new ArrayKnowledgeRetriever());

        $chunks = $orchestrator->retrieveForAgent(
            new AgentDefinition(
                name: 'support',
                knowledge: ['policies'],
            ),
            'returns within 30 days',
        );

        $this->assertCount(1, $chunks);
        $this->assertStringContainsString('30 days', $chunks[0]->content);
    }
}
