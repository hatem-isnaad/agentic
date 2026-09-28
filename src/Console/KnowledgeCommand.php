<?php

namespace Agentic\Console;

use Agentic\Admin\DTO\KnowledgeSourceData;
use Agentic\Admin\Services\KnowledgeSourceAdminService;
use Agentic\Console\Concerns\ConfirmsBeforeSave;
use Agentic\Knowledge\KnowledgeIngestor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

final class KnowledgeCommand extends Command
{
    use ConfirmsBeforeSave;

    protected $signature = 'agentic:knowledge
        {action=create : create|list|delete|ingest}
        {--name=}
        {--slug=}
        {--driver=array : array|vector}
        {--description=}
        {--text= : Raw text to ingest}
        {--status=published}';

    protected $description = 'Create or ingest knowledge sources (asks one question at a time, then confirms)';

    public function __construct(
        private KnowledgeSourceAdminService $sources,
        private KnowledgeIngestor $ingestor,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ((string) $this->argument('action')) {
            'create' => $this->createSource(),
            'list' => $this->listSources(),
            'delete' => $this->deleteSource(),
            'ingest' => $this->ingestText(),
            default => $this->invalid(),
        };
    }

    private function createSource(): int
    {
        $interactive = $this->wantsPrompts($this->option('name') !== null ? (string) $this->option('name') : null);
        $name = $interactive ? (string) $this->ask('Source name', 'Policies') : (string) $this->option('name');
        $slug = $interactive ? (string) $this->ask('Slug', Str::slug($name)) : (string) ($this->option('slug') ?: Str::slug($name));
        $driver = $interactive ? (string) $this->choice('Driver', ['array', 'vector'], 0) : (string) ($this->option('driver') ?: 'array');
        $description = $interactive ? (string) $this->ask('Description', $name) : (string) ($this->option('description') ?: $name);
        $status = (string) ($this->option('status') ?: 'published');

        if (! $this->summarizeAndConfirm([
            ['name', $name],
            ['slug', $slug],
            ['driver', $driver],
        ], 'Save this knowledge source?')) {
            return self::SUCCESS;
        }

        $this->sources->store(KnowledgeSourceData::fromValidated([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'driver' => $driver,
            'status' => $status,
        ]));

        $this->components->info('Saved knowledge source ['.$slug.']. Ingest text with agentic:knowledge ingest --slug='.$slug);

        return self::SUCCESS;
    }

    private function listSources(): int
    {
        $rows = $this->sources->list();
        if ($rows === []) {
            $this->line('No knowledge sources.');

            return self::SUCCESS;
        }

        $this->table(
            ['slug', 'name', 'driver', 'status'],
            array_map(fn ($source) => [$source->slug, $source->name, $source->driver, $source->status ?? ''], $rows),
        );

        return self::SUCCESS;
    }

    private function deleteSource(): int
    {
        $slug = (string) ($this->option('slug') ?: $this->ask('Knowledge slug'));
        if (! $this->option('no-interaction') && ! $this->confirm('Delete knowledge ['.$slug.']?', false)) {
            return self::SUCCESS;
        }

        if (! $this->sources->delete($slug)) {
            $this->error('Knowledge source not found.');

            return self::FAILURE;
        }

        $this->components->info('Deleted.');

        return self::SUCCESS;
    }

    private function ingestText(): int
    {
        $interactive = $this->wantsPrompts($this->option('text') !== null ? (string) $this->option('text') : null);
        $slug = $interactive ? (string) $this->ask('Knowledge slug') : (string) $this->option('slug');
        $text = $interactive ? (string) $this->ask('Paste the text to ingest') : (string) ($this->option('text') ?: '');

        if ($slug === '' || $text === '') {
            $this->error('Slug and text are required.');

            return self::FAILURE;
        }

        if (! $this->summarizeAndConfirm([
            ['slug', $slug],
            ['chars', (string) strlen($text)],
        ], 'Ingest this text?')) {
            return self::SUCCESS;
        }

        try {
            $this->ingestor->ingest($slug, ['format' => 'text', 'raw_text' => $text], true);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Ingested into ['.$slug.'].');

        return self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('Action must be create, list, delete, or ingest.');

        return self::FAILURE;
    }
}
