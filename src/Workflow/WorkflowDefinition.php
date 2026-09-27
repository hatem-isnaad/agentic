<?php

namespace Agentic\Workflow;

final readonly class WorkflowDefinition
{
    /**
     * @param  list<array<string, mixed>>  $steps
     */
    public function __construct(
        public string $slug,
        public string $name,
        public array $steps,
        public ?string $description = null,
        public ?string $status = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'definition' => ['steps' => $this->steps],
        ];
    }
}
