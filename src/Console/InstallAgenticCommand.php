<?php

namespace Agentic\Console;

use Agentic\Console\Support\EnvFileWriter;
use Illuminate\Console\Command;

final class InstallAgenticCommand extends Command
{
    protected $signature = 'agentic:install
        {--force : Overwrite published config}
        {--quick : Publish config only — no interactive wizard}
        {--wizard : Force interactive wizard even in CI}';

    protected $description = 'Install Agentic: interactive .env setup (default) + publish config';

    public function handle(AgenticSetupWizard $wizard): int
    {
        $this->publishConfig();

        $runWizard = $this->option('wizard')
            || (! $this->option('quick') && $this->input->isInteractive());

        if ($runWizard) {
            $this->call('vendor:publish', ['--tag' => 'ai-config', '--force' => false]);

            return $wizard->run($this);
        }

        $this->printQuickSteps();

        return self::SUCCESS;
    }

    private function publishConfig(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'agentic-config',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->newLine();
        $this->components->info('Agentic config published (host overrides: config/agentic.php).');
    }

    private function printQuickSteps(): void
    {
        $this->line('Non-interactive install. For guided setup run: php artisan agentic:install');
        $this->line('');
        $this->line('Next steps:');
        $this->line('  1. php artisan vendor:publish --tag=ai-config');
        $this->line('  2. php artisan migrate');
        $this->line('  3. php artisan vendor:publish --tag=agentic-admin-assets --force');
        $this->line('  4. php artisan vendor:publish --tag=agentic-widget-assets --force');
        $this->line('  5. php artisan agentic:rag-validate');
        $this->line('');
        $this->line('Docs: vendor/hatem-isnaad/agentic/docs/INSTALL_WIZARD.md');
        $this->line('       vendor/hatem-isnaad/agentic/docs/START_HERE.md');
    }
}
