<?php

namespace Agentic\Console;

use Illuminate\Console\Command;

final class InstallAgenticCommand extends Command
{
    protected $signature = 'agentic:install {--force : Overwrite published config}';

    protected $description = 'Publish Agentic config and print host setup steps';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'agentic-config',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->newLine();
        $this->components->info('Agentic config published.');

        $this->line('Install (if not done yet): composer require hatem-isnaad/agentic');
        $this->line('  (laravel/ai is a Composer dependency — no separate require needed.)');
        $this->line('');
        $this->line('Next steps in your Laravel host app:');
        $this->line('  1. php artisan migrate');
        $this->line('  2. php artisan vendor:publish --tag=ai-config   # Laravel AI SDK');
        $this->line('  3. Copy keys from vendor/hatem-isnaad/agentic/.env.example into .env');
        $this->line('  4. php artisan agentic:rag-validate --offline');
        $this->line('  5. php artisan agentic:rag-validate            # after AI keys / Ollama');
        $this->line('');
        $this->line('Start here: vendor/hatem-isnaad/agentic/docs/START_HERE.md');
        $this->line('Handbook:  vendor/hatem-isnaad/agentic/docs/DEVELOPER_HANDBOOK.md');
        $this->line('Quickstart: vendor/hatem-isnaad/agentic/docs/DEVELOPER_QUICKSTART.md');
        $this->line('           vendor/hatem-isnaad/agentic/docs/CONFIGURE_BY_CODE.md');
        $this->line('Docs:      vendor/hatem-isnaad/agentic/docs/HOST_BOOTSTRAP.md');
        $this->line('           vendor/hatem-isnaad/agentic/docs/PRODUCTION_CHECKLIST.md');
        $this->line('');
        $this->line('Custom PHP tools: set code_tools.paths + namespace in config/agentic.php, then:');
        $this->line('  php artisan agentic:make-code-tool MyTool');
        $this->line('  php artisan agentic:code-tools-sync');

        return self::SUCCESS;
    }
}
