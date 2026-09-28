<?php

namespace Agentic\Console;

use Illuminate\Console\Command;

final class MakeCommand extends Command
{
    protected $signature = 'agentic:make
        {type? : connection|http-tool|code-tool|embed-token|channel-account|agent|skill|knowledge|evaluation}';

    protected $description = 'Interactive create wizard (asks questions, shows a summary, then saves)';

    /** @var list<string> */
    private const TYPES = [
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
        $type = $this->argument('type');
        if ($type === null || $type === '') {
            $type = $this->choice('What do you want to create?', self::TYPES, 0);
        }

        return match ($type) {
            'connection' => $this->call('agentic:connection', ['action' => 'create']),
            'http-tool' => $this->call('agentic:http-tool', ['action' => 'create']),
            'code-tool' => $this->askCodeTool(),
            'embed-token' => $this->call('agentic:widget-embed-token', ['action' => 'create']),
            'channel-account' => $this->call('agentic:channel-account', ['action' => 'create']),
            'agent' => $this->call('agentic:agent', ['action' => 'create']),
            'skill' => $this->call('agentic:skill', ['action' => 'create']),
            'knowledge' => $this->call('agentic:knowledge', ['action' => 'create']),
            'evaluation' => $this->call('agentic:evaluation', ['action' => 'create']),
            default => $this->badType((string) $type),
        };
    }

    private function askCodeTool(): int
    {
        $name = (string) $this->ask('PHP class name', 'LookupOrder');
        if (! $this->confirm('Create app/Agentic/Tools/Custom/'.$name.'Tool.php?', true)) {
            return self::SUCCESS;
        }

        return $this->call('agentic:make-code-tool', ['name' => $name]);
    }

    private function badType(string $type): int
    {
        $this->error('Unknown type ['.$type.']. Use: '.implode(', ', self::TYPES).'.');

        return self::FAILURE;
    }
}
