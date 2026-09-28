<?php

namespace Agentic\Console;

use Agentic\Admin\DTO\AgentData;
use Agentic\Admin\Services\AgentAdminService;
use Agentic\Console\Concerns\ConfirmsBeforeSave;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class AgentCommand extends Command
{
    use ConfirmsBeforeSave;

    protected $signature = 'agentic:agent
        {action=create : create|list|delete}
        {--name=}
        {--slug=}
        {--instructions=}
        {--provider=}
        {--model=}
        {--skills= : Comma-separated skill slugs}
        {--tools= : Comma-separated tool slugs}
        {--knowledge= : Comma-separated knowledge slugs}
        {--status=published}';

    protected $description = 'Create or manage agents (asks one question at a time, then confirms)';

    public function __construct(private AgentAdminService $agents)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ((string) $this->argument('action')) {
            'create' => $this->createAgent(),
            'list' => $this->listAgents(),
            'delete' => $this->deleteAgent(),
            default => $this->invalid(),
        };
    }

    private function createAgent(): int
    {
        $interactive = $this->wantsPrompts($this->option('name') !== null ? (string) $this->option('name') : null);
        $name = $interactive ? (string) $this->ask('Agent name', 'Support') : (string) $this->option('name');
        $slug = $interactive ? (string) $this->ask('Slug', Str::slug($name)) : (string) ($this->option('slug') ?: Str::slug($name));
        $instructions = $interactive ? (string) $this->ask('Instructions', 'Help the visitor.') : (string) ($this->option('instructions') ?: '');
        $provider = $interactive ? (string) $this->ask('AI provider (empty = package default)', '') : (string) ($this->option('provider') ?: '');
        $model = $interactive ? (string) $this->ask('Model (empty = package default)', '') : (string) ($this->option('model') ?: '');
        $skills = $interactive ? $this->splitCsv((string) $this->ask('Skill slugs (comma, optional)', '')) : $this->splitCsv((string) ($this->option('skills') ?: ''));
        $tools = $interactive ? $this->splitCsv((string) $this->ask('Tool slugs (comma, optional)', '')) : $this->splitCsv((string) ($this->option('tools') ?: ''));
        $knowledge = $interactive ? $this->splitCsv((string) $this->ask('Knowledge slugs (comma, optional)', '')) : $this->splitCsv((string) ($this->option('knowledge') ?: ''));
        $status = (string) ($this->option('status') ?: 'published');

        if (! $this->summarizeAndConfirm([
            ['name', $name],
            ['slug', $slug],
            ['status', $status],
            ['skills', implode(',', $skills) ?: '(none)'],
            ['tools', implode(',', $tools) ?: '(none)'],
        ], 'Save this agent?')) {
            return self::SUCCESS;
        }

        $this->agents->store(AgentData::fromValidated([
            'name' => $name,
            'slug' => $slug,
            'instructions' => $instructions !== '' ? $instructions : null,
            'status' => $status,
            'provider' => $provider !== '' ? $provider : null,
            'model' => $model !== '' ? $model : null,
            'skills' => $skills,
            'tools' => $tools,
            'knowledge' => $knowledge,
        ]));

        $this->components->info('Saved agent ['.$slug.'].');

        return self::SUCCESS;
    }

    private function listAgents(): int
    {
        $rows = $this->agents->list();
        if ($rows === []) {
            $this->line('No agents.');

            return self::SUCCESS;
        }

        $this->table(
            ['slug', 'name', 'status'],
            array_map(fn ($agent) => [$agent->slug, $agent->name, $agent->status ?? ''], $rows),
        );

        return self::SUCCESS;
    }

    private function deleteAgent(): int
    {
        $slug = (string) ($this->option('slug') ?: $this->ask('Agent slug'));
        if (! $this->option('no-interaction') && ! $this->confirm('Delete agent ['.$slug.']?', false)) {
            return self::SUCCESS;
        }

        if (! $this->agents->delete($slug)) {
            $this->error('Agent not found.');

            return self::FAILURE;
        }

        $this->components->info('Deleted.');

        return self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('Action must be create, list, or delete.');

        return self::FAILURE;
    }
}
