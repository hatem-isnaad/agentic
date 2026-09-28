<?php

namespace Agentic\Console;

use Agentic\Admin\Services\ToolAdminService;
use Agentic\Contracts\Repositories\ToolRepository;
use Agentic\Enums\Status;
use Agentic\Exceptions\ToolNotFoundException;
use Agentic\Models\Connection;
use Agentic\Tool\HttpToolTester;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class HttpToolCommand extends Command
{
    protected $signature = 'agentic:http-tool
        {action=create : create|list|test|clone}
        {slug? : Tool slug (test)}
        {--name=}
        {--slug=}
        {--method=GET}
        {--url=}
        {--connection=}
        {--param= : Path/query argument name, e.g. reference}
        {--description=}
        {--arg=* : Test argument as name=value}';

    protected $description = 'Create, list, test, or clone HTTP tools (asks one question at a time, then confirms)';

    public function __construct(private ToolRepository $tools)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ((string) $this->argument('action')) {
            'create' => $this->createTool(),
            'list' => $this->listTools(),
            'test' => $this->testTool(),
            'clone' => $this->cloneTool(),
            default => $this->invalid(),
        };
    }

    private function createTool(): int
    {
        $interactive = ! $this->option('no-interaction') && $this->option('url') === null;
        $connections = Connection::query()->orderBy('slug')->pluck('slug')->all();

        $name = $interactive ? (string) $this->ask('Tool name', 'Get order') : (string) $this->option('name');
        $slug = $interactive ? (string) $this->ask('Slug', Str::slug($name)) : (string) ($this->option('slug') ?: Str::slug($name));
        $method = $interactive ? (string) $this->choice('HTTP method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], 0) : strtoupper((string) ($this->option('method') ?: 'GET'));
        $url = $interactive ? (string) $this->ask('URL (use {placeholders})', 'https://api.example.com/v1/orders/{id}') : (string) $this->option('url');
        $param = $interactive ? (string) $this->ask('Input argument name (empty = none)', 'id') : (string) ($this->option('param') ?: '');
        $connection = $this->option('connection');
        if ($interactive) {
            $choices = array_merge(['(none)'], $connections);
            $picked = (string) $this->choice('Auth connection', $choices === ['(none)'] ? ['(none) — create one first with agentic:connection'] : $choices, 0);
            $connection = str_starts_with($picked, '(') ? null : $picked;
        }
        $description = $interactive ? (string) $this->ask('Description', $name) : (string) ($this->option('description') ?: $name);

        $definition = array_filter([
            'method' => $method,
            'url' => $url,
            'connection' => $connection,
            'timeout' => 20,
            'input_schema' => $param !== '' ? [
                'type' => 'object',
                'properties' => [$param => ['type' => 'string']],
                'required' => [$param],
            ] : null,
        ]);

        $this->table(['Field', 'Value'], [
            ['name', $name],
            ['slug', $slug],
            ['method', $method],
            ['url', $url],
            ['connection', $connection ?: '(none)'],
            ['param', $param ?: '(none)'],
        ]);

        if ($interactive && ! $this->confirm('Save this HTTP tool?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $this->tools->save([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'driver' => 'http',
            'status' => Status::Published->value,
            'definition' => $definition,
            'publish' => true,
        ]);

        $this->components->info('Saved HTTP tool ['.$slug.']. Attach the slug on a skill and agent.');

        return self::SUCCESS;
    }

    private function listTools(): int
    {
        $rows = array_values(array_filter($this->tools->all(), fn ($tool) => $tool->driver === 'http'));
        if ($rows === []) {
            $this->line('No HTTP tools.');

            return self::SUCCESS;
        }

        $this->table(
            ['slug', 'url', 'connection'],
            array_map(fn ($tool) => [
                $tool->name,
                $tool->configuration['url'] ?? '',
                $tool->connection ?? '',
            ], $rows),
        );

        return self::SUCCESS;
    }

    private function testTool(): int
    {
        $tester = app(HttpToolTester::class);
        $interactive = ! $this->option('no-interaction');
        $slug = (string) ($this->argument('slug') ?: $this->option('slug') ?: '');

        if ($slug === '' && $interactive) {
            $http = array_values(array_filter($this->tools->all(), fn ($tool) => $tool->driver === 'http'));
            $slugs = array_map(fn ($tool) => $tool->name, $http);
            if ($slugs === []) {
                $this->error('No HTTP tools. Create one with agentic:http-tool create.');

                return self::FAILURE;
            }
            $slug = (string) $this->choice('Which HTTP tool?', $slugs, 0);
        }

        if ($slug === '') {
            $this->error('Pass a tool slug: php artisan agentic:http-tool test {slug}');

            return self::FAILURE;
        }

        try {
            $fields = $tester->argumentFields($slug);
        } catch (ToolNotFoundException) {
            $this->error('Tool ['.$slug.'] not found.');

            return self::FAILURE;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $arguments = $this->collectTestArguments($fields, $interactive);

        $this->table(['Field', 'Value'], array_map(
            fn (string $key, mixed $value) => [$key, is_scalar($value) ? (string) $value : json_encode($value)],
            array_keys($arguments),
            array_values($arguments),
        ) ?: [['(none)', '']]);

        if ($interactive && ! $this->confirm('Send this HTTP request now?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        try {
            $result = $tester->run($slug, $arguments);
        } catch (ToolNotFoundException) {
            $this->error('Tool ['.$slug.'] not found.');

            return self::FAILURE;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $request = is_array($result['request'] ?? null) ? $result['request'] : [];
        if ($request !== []) {
            $this->line(sprintf(
                '%s %s',
                strtoupper((string) ($request['method'] ?? 'GET')),
                (string) ($request['url'] ?? ''),
            ));
        }

        if ($result['success']) {
            $this->components->info('OK ('.$result['duration_ms'].' ms)');
            $this->line((string) json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->error($result['error'] ?: 'Request failed.');
        if ($result['data'] !== null) {
            $this->line((string) json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return self::FAILURE;
    }

    /**
     * @param  list<array{name: string, type: string, required: bool, description: string}>  $fields
     * @return array<string, mixed>
     */
    private function collectTestArguments(array $fields, bool $interactive): array
    {
        $fromFlags = [];
        foreach ((array) $this->option('arg') as $pair) {
            if (! is_string($pair) || ! str_contains($pair, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $pair, 2);
            $fromFlags[trim($key)] = $this->castArgument(trim($value), 'string');
        }

        if (! $interactive) {
            return $fromFlags;
        }

        $arguments = $fromFlags;
        foreach ($fields as $field) {
            if (array_key_exists($field['name'], $arguments)) {
                continue;
            }

            $label = $field['name'].($field['required'] ? ' (required)' : ' (optional)');
            if ($field['description'] !== '') {
                $label .= ' — '.$field['description'];
            }

            $answer = (string) $this->ask($label, $field['required'] ? null : '');
            if ($answer === '' && ! $field['required']) {
                continue;
            }

            $arguments[$field['name']] = $this->castArgument($answer, $field['type']);
        }

        return $arguments;
    }

    private function castArgument(string $value, string $type): mixed
    {
        return match ($type) {
            'integer', 'int' => (int) $value,
            'number' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }

    private function cloneTool(): int
    {
        $interactive = ! $this->option('no-interaction');
        $source = (string) ($this->argument('slug') ?: '');

        if ($source === '' && $interactive) {
            $rows = $this->tools->all();
            $slugs = array_map(fn ($tool) => $tool->name, $rows);
            if ($slugs === []) {
                $this->error('No tools to clone.');

                return self::FAILURE;
            }
            $source = (string) $this->choice('Clone which tool?', $slugs, 0);
        }

        if ($source === '') {
            $this->error('Pass a tool slug: php artisan agentic:http-tool clone {slug}');

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: '');
        $newSlug = (string) ($this->option('slug') ?: '');

        if ($interactive) {
            $name = (string) $this->ask('New name (empty = “original (copy)”)', $name !== '' ? $name : null) ?: $name;
            $newSlug = (string) $this->ask('New slug (empty = auto)', $newSlug !== '' ? $newSlug : null) ?: $newSlug;
        }

        $this->table(['Field', 'Value'], [
            ['source', $source],
            ['name', $name !== '' ? $name : '(auto)'],
            ['slug', $newSlug !== '' ? $newSlug : '(auto)'],
        ]);

        if ($interactive && ! $this->confirm('Clone this tool?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        try {
            $clone = app(ToolAdminService::class)->clone(
                $source,
                $name !== '' ? $name : null,
                $newSlug !== '' ? $newSlug : null,
            );
        } catch (ToolNotFoundException) {
            $this->error('Tool ['.$source.'] not found.');

            return self::FAILURE;
        }

        $this->components->info('Cloned to ['.$clone->slug.']. Edit it in Admin → Tools or attach the slug on a skill.');

        return self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('Action must be create, list, test, or clone.');

        return self::FAILURE;
    }
}
