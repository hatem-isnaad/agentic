<?php

namespace Agentic\Console;

use Agentic\Admin\DTO\SkillData;
use Agentic\Admin\Services\SkillAdminService;
use Agentic\Console\Concerns\ConfirmsBeforeSave;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class SkillCommand extends Command
{
    use ConfirmsBeforeSave;

    protected $signature = 'agentic:skill
        {action=create : create|list|delete}
        {--name=}
        {--slug=}
        {--description=}
        {--tools= : Comma-separated tool slugs}
        {--knowledge= : Comma-separated knowledge slugs}
        {--status=published}';

    protected $description = 'Create or manage skills (asks one question at a time, then confirms)';

    public function __construct(private SkillAdminService $skills)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ((string) $this->argument('action')) {
            'create' => $this->createSkill(),
            'list' => $this->listSkills(),
            'delete' => $this->deleteSkill(),
            default => $this->invalid(),
        };
    }

    private function createSkill(): int
    {
        $interactive = $this->wantsPrompts($this->option('name') !== null ? (string) $this->option('name') : null);
        $name = $interactive ? (string) $this->ask('Skill name', 'Orders') : (string) $this->option('name');
        $slug = $interactive ? (string) $this->ask('Slug', Str::slug($name)) : (string) ($this->option('slug') ?: Str::slug($name));
        $description = $interactive ? (string) $this->ask('Description', $name) : (string) ($this->option('description') ?: $name);
        $tools = $interactive ? $this->splitCsv((string) $this->ask('Tool slugs (comma)', '')) : $this->splitCsv((string) ($this->option('tools') ?: ''));
        $knowledge = $interactive ? $this->splitCsv((string) $this->ask('Knowledge slugs (comma, optional)', '')) : $this->splitCsv((string) ($this->option('knowledge') ?: ''));
        $status = (string) ($this->option('status') ?: 'published');

        if (! $this->summarizeAndConfirm([
            ['name', $name],
            ['slug', $slug],
            ['tools', implode(',', $tools) ?: '(none)'],
            ['knowledge', implode(',', $knowledge) ?: '(none)'],
        ], 'Save this skill?')) {
            return self::SUCCESS;
        }

        $this->skills->store(SkillData::fromValidated([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'status' => $status,
            'tools' => $tools,
            'knowledge' => $knowledge,
        ]));

        $this->components->info('Saved skill ['.$slug.']. Attach it on an agent.');

        return self::SUCCESS;
    }

    private function listSkills(): int
    {
        $rows = $this->skills->list();
        if ($rows === []) {
            $this->line('No skills.');

            return self::SUCCESS;
        }

        $this->table(
            ['slug', 'name', 'tools'],
            array_map(fn ($skill) => [$skill->slug, $skill->name, implode(',', $skill->tools)], $rows),
        );

        return self::SUCCESS;
    }

    private function deleteSkill(): int
    {
        $slug = (string) ($this->option('slug') ?: $this->ask('Skill slug'));
        if (! $this->option('no-interaction') && ! $this->confirm('Delete skill ['.$slug.']?', false)) {
            return self::SUCCESS;
        }

        if (! $this->skills->delete($slug)) {
            $this->error('Skill not found.');

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
