<?php

namespace Agentic\Console;

use Agentic\Mcp\McpToolSyncService;
use Illuminate\Console\Command;

final class SyncMcpToolsCommand extends Command
{
    protected $signature = 'agentic:mcp-sync {server? : MCP server name from config/mcp.php}';

    protected $description = 'Discover MCP server tools and register them in the Agentic tool registry';

    public function handle(McpToolSyncService $sync): int
    {
        $server = $this->argument('server');

        if (is_string($server) && $server !== '') {
            $tools = $sync->sync($server);
            $this->info('Registered '.count($tools).' tools from MCP server ['.$server.'].');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($sync->servers() as $name) {
            $tools = $sync->sync($name);
            $count += count($tools);
            $this->line($name.': '.count($tools).' tools');
        }

        $this->info('Registered '.$count.' MCP tools total.');

        return self::SUCCESS;
    }
}
