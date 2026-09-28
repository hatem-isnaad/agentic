<?php

namespace Agentic\Console;

use Illuminate\Console\Command;

final class ManageCommand extends Command
{
    protected $signature = 'agentic:manage
        {action? : create|list|delete|test|clone}
        {resource? : connection|http-tool|code-tool|embed-token|channel-account|agent|skill|knowledge|evaluation}';

    protected $description = 'Do anything from the CLI: pick an action and a resource (no UI needed)';

    /** @var list<string> */
    private const RESOURCES = [
        'connection',
        'http-tool',
        'code-tool',
        'embed-token',
        'channel-account',
        'agent',
        'skill',
        'knowledge',
        'evaluation',
    ];

    public function handle(): int
    {
        $action = (string) ($this->argument('action') ?: '');
        if ($action === '') {
            $action = (string) $this->choice('What do you want to do?', ['create', 'list', 'delete', 'test', 'clone'], 0);
        }

        $resource = (string) ($this->argument('resource') ?: '');
        if ($resource === '') {
            $resource = (string) $this->choice('Which resource?', self::RESOURCES, 0);
        }

        if (! in_array($resource, self::RESOURCES, true)) {
            $this->error('Unknown resource. Use: '.implode(', ', self::RESOURCES));

            return self::FAILURE;
        }

        return match ($action) {
            'create' => $this->create($resource),
            'list' => $this->listResource($resource),
            'delete' => $this->delete($resource),
            'test' => $this->test($resource),
            'clone' => $this->clone($resource),
            default => $this->badAction($action),
        };
    }

    private function create(string $resource): int
    {
        return match ($resource) {
            'connection' => $this->call('agentic:connection', ['action' => 'create']),
            'http-tool' => $this->call('agentic:http-tool', ['action' => 'create']),
            'code-tool' => $this->call('agentic:make', ['type' => 'code-tool']),
            'embed-token' => $this->call('agentic:widget-embed-token', ['action' => 'create']),
            'channel-account' => $this->call('agentic:channel-account', ['action' => 'create']),
            'agent' => $this->call('agentic:agent', ['action' => 'create']),
            'skill' => $this->call('agentic:skill', ['action' => 'create']),
            'knowledge' => $this->call('agentic:knowledge', ['action' => 'create']),
            'evaluation' => $this->call('agentic:evaluation', ['action' => 'create']),
            default => self::FAILURE,
        };
    }

    private function listResource(string $resource): int
    {
        return match ($resource) {
            'connection' => $this->call('agentic:connection', ['action' => 'list']),
            'http-tool' => $this->call('agentic:http-tool', ['action' => 'list']),
            'code-tool' => $this->call('agentic:code-tools-sync'),
            'embed-token' => $this->call('agentic:widget-embed-token', ['action' => 'list']),
            'channel-account' => $this->call('agentic:channel-account', ['action' => 'list']),
            'agent' => $this->call('agentic:agent', ['action' => 'list']),
            'skill' => $this->call('agentic:skill', ['action' => 'list']),
            'knowledge' => $this->call('agentic:knowledge', ['action' => 'list']),
            'evaluation' => $this->call('agentic:evaluation', ['action' => 'list']),
            default => self::FAILURE,
        };
    }

    private function delete(string $resource): int
    {
        return match ($resource) {
            'connection' => $this->call('agentic:connection', ['action' => 'delete']),
            'http-tool' => $this->unavailableDelete('HTTP tools: open Admin → Tools, or delete the row in the database.'),
            'code-tool' => $this->unavailableDelete('Code tools: remove the PHP class, then agentic:code-tools-sync.'),
            'embed-token' => $this->call('agentic:widget-embed-token', ['action' => 'revoke']),
            'channel-account' => $this->call('agentic:channel-account', ['action' => 'delete']),
            'agent' => $this->call('agentic:agent', ['action' => 'delete']),
            'skill' => $this->call('agentic:skill', ['action' => 'delete']),
            'knowledge' => $this->call('agentic:knowledge', ['action' => 'delete']),
            'evaluation' => $this->unavailableDelete('Evaluations are append-only scores.'),
            default => self::FAILURE,
        };
    }

    private function test(string $resource): int
    {
        return match ($resource) {
            'http-tool' => $this->call('agentic:http-tool', ['action' => 'test']),
            default => $this->unavailableDelete('Test is only for HTTP tools: php artisan agentic:http-tool test'),
        };
    }

    private function clone(string $resource): int
    {
        return match ($resource) {
            'http-tool' => $this->call('agentic:http-tool', ['action' => 'clone']),
            default => $this->unavailableDelete('Clone is only for tools: php artisan agentic:http-tool clone {slug}'),
        };
    }

    private function unavailableDelete(string $message): int
    {
        $this->warn($message);

        return self::SUCCESS;
    }

    private function badAction(string $action): int
    {
        $this->error('Action ['.$action.'] must be create, list, delete, test, or clone.');

        return self::FAILURE;
    }
}
