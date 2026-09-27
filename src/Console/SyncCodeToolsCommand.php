<?php

namespace Agentic\Console;

use Agentic\Tool\Discovery\CustomCodeToolDiscovery;
use Agentic\Tool\Handlers\HandlerRegistry;
use Illuminate\Console\Command;

final class SyncCodeToolsCommand extends Command
{
    protected $signature = 'agentic:code-tools-sync';

    protected $description = 'Register custom code tool classes from app/Agentic/Tools/Custom';

    public function handle(CustomCodeToolDiscovery $discovery, HandlerRegistry $registry): int
    {
        $count = $discovery->registerDiscovered($registry);
        $this->info("Registered {$count} new handler(s).");

        foreach ($discovery->discover() as $row) {
            $this->line(sprintf(
                ' - %s (%s) %s',
                $row['handler'],
                class_basename($row['class']),
                $row['registered'] ? '[ok]' : '[pending]',
            ));
        }

        return self::SUCCESS;
    }
}
