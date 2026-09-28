<?php

namespace Agentic\Console;

use Agentic\Connections\ConnectionRequestExtras;
use Agentic\Connections\ConnectionService;
use Agentic\Connections\OAuth2TokenManager;
use Agentic\Models\Connection;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class ConnectionCommand extends Command
{
    protected $signature = 'agentic:connection
        {action=create : create|list|show|delete|refresh}
        {--name= : Label}
        {--slug= : Unique slug}
        {--type= : bearer|basic|header|query|oauth2}
        {--status=active}
        {--token= : Bearer token stored on the connection}
        {--username=}
        {--password=}
        {--header-name= : Custom header or query key}
        {--value= : Custom header or query value}
        {--grant= : refresh_token|client_credentials}
        {--token-url=}
        {--scope=}
        {--client-id=}
        {--client-secret=}
        {--refresh-token=}
        {--header=* : Extra request header Key:Value}
        {--query=* : Extra request query Key:Value}
        {--auth-header=* : Extra token-request header Key:Value}
        {--auth-query=* : Extra token-request query Key:Value}
        {--auth-body=* : Extra token-request form field Key:Value}
        {--id= : Row id for show|delete|refresh}';

    protected $description = 'Create or manage HTTP-tool connections (bearer / OAuth2 refresh)';

    public function __construct(private ConnectionService $connections)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ((string) $this->argument('action')) {
            'create' => $this->createConnection(),
            'list' => $this->listConnections(),
            'show' => $this->showConnection(),
            'delete' => $this->deleteConnection(),
            'refresh' => $this->refreshConnection(),
            default => $this->invalid(),
        };
    }

    private function createConnection(): int
    {
        $interactive = ! $this->option('no-interaction') && $this->option('name') === null;

        $name = $interactive ? (string) $this->ask('Connection name', 'Limenos merchant') : (string) $this->option('name');
        $slug = $interactive ? (string) $this->ask('Slug', Str::slug($name)) : (string) ($this->option('slug') ?: Str::slug($name));
        $type = $interactive ? (string) $this->choice('Auth type', ConnectionService::types(), 0) : (string) ($this->option('type') ?: 'bearer');
        $status = (string) ($this->option('status') ?: 'active');

        $data = ['name' => $name, 'slug' => $slug, 'type' => $type, 'status' => $status];

        if ($type === 'bearer') {
            $data['token'] = $interactive ? (string) $this->secret('Bearer token') : (string) $this->option('token');
        } elseif ($type === 'basic') {
            $data['username'] = $interactive ? (string) $this->ask('Username') : (string) $this->option('username');
            $data['password'] = $interactive ? (string) $this->secret('Password') : (string) $this->option('password');
        } elseif (in_array($type, ['header', 'query'], true)) {
            $data['config'] = ['name' => $interactive ? (string) $this->ask('Parameter name', 'X-Api-Key') : (string) ($this->option('header-name') ?: 'X-Api-Key')];
            $data['value'] = $interactive ? (string) $this->ask('Value') : (string) $this->option('value');
        } else {
            $grant = $interactive ? (string) $this->choice('Grant type', ['refresh_token', 'client_credentials'], 0) : (string) ($this->option('grant') ?: 'refresh_token');
            $data['grant_type'] = $grant;
            $data['token_url'] = $interactive ? (string) $this->ask('Token URL') : (string) $this->option('token-url');
            $data['scope'] = $interactive ? (string) $this->ask('Scope (optional)', '') : (string) ($this->option('scope') ?: '');
            $data['client_id'] = $interactive ? (string) $this->ask('Client id') : (string) $this->option('client-id');
            $data['client_secret'] = $interactive ? (string) $this->secret('Client secret') : (string) $this->option('client-secret');
            if ($grant === 'refresh_token') {
                $data['refresh_token'] = $interactive ? (string) $this->secret('Refresh token') : (string) $this->option('refresh-token');
            }
        }

        $data['headers'] = $this->collectPairs('Extra request header (Name:value, empty to stop)', $this->option('header'), $interactive);
        $data['query'] = $this->collectPairs('Extra request query (name=value, empty to stop)', $this->option('query'), $interactive);
        if ($type === 'oauth2') {
            $data['auth_headers'] = $this->collectPairs('Token-request header (Name:value, empty to stop)', $this->option('auth-header'), $interactive);
            $data['auth_query'] = $this->collectPairs('Token-request query (name=value, empty to stop)', $this->option('auth-query'), $interactive);
            $data['auth_body'] = $this->collectPairs('Token-request body field (name=value, empty to stop)', $this->option('auth-body'), $interactive);
        }

        $this->table(['Field', 'Value'], [
            ['name', $name],
            ['slug', $slug],
            ['type', $type],
            ['status', $status],
        ]);

        if ($interactive && ! $this->confirm('Save this connection?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        try {
            $row = $this->connections->create($data);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Saved connection ['.$row->slug.']. Point HTTP tools at definition.connection = '.$row->slug);

        return self::SUCCESS;
    }

    private function listConnections(): int
    {
        $rows = Connection::query()->orderBy('slug')->get();
        if ($rows->isEmpty()) {
            $this->line('No connections.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'slug', 'type', 'status', 'has_secrets'],
            $rows->map(fn (Connection $row) => [$row->id, $row->slug, $row->type, $row->status, $row->getRawOriginal('credentials') !== null ? 'yes' : 'no'])->all(),
        );

        return self::SUCCESS;
    }

    private function showConnection(): int
    {
        $row = $this->findRow();
        if ($row === null) {
            return self::FAILURE;
        }

        $this->line(json_encode($this->connections->toAdminArray($row), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function deleteConnection(): int
    {
        $row = $this->findRow();
        if ($row === null) {
            return self::FAILURE;
        }

        if (! $this->option('no-interaction') && ! $this->confirm('Delete connection ['.$row->slug.']?', false)) {
            return self::SUCCESS;
        }

        $row->delete();
        $this->components->info('Deleted.');

        return self::SUCCESS;
    }

    private function refreshConnection(): int
    {
        $row = $this->findRow();
        if ($row === null) {
            return self::FAILURE;
        }

        try {
            app(OAuth2TokenManager::class)->refresh($row);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Access token refreshed for ['.$row->slug.'].');

        return self::SUCCESS;
    }

    private function findRow(): ?Connection
    {
        $id = $this->option('id');
        $slug = $this->option('slug');
        if ($id === null && $slug === null && ! $this->option('no-interaction')) {
            $slug = $this->ask('Connection slug or id');
        }

        $row = is_numeric((string) ($id ?: $slug))
            ? Connection::query()->find((int) ($id ?: $slug))
            : Connection::query()->where('slug', $slug ?: $id)->first();

        if ($row === null) {
            $this->error('Connection not found.');
        }

        return $row;
    }

    /**
     * @param  list<string>|string|null  $option
     * @return array<string, string>
     */
    private function collectPairs(string $question, mixed $option, bool $interactive): array
    {
        $map = ConnectionRequestExtras::fromCliPairs($option);
        if (! $interactive) {
            return $map;
        }

        while (true) {
            $pair = (string) $this->ask($question, '');
            if ($pair === '') {
                break;
            }
            $map = array_merge($map, ConnectionRequestExtras::fromCliPairs([$pair]));
        }

        return $map;
    }

    private function invalid(): int
    {
        $this->error('Action must be create, list, show, delete, or refresh.');

        return self::FAILURE;
    }
}
