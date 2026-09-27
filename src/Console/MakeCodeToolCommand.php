<?php

namespace Agentic\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class MakeCodeToolCommand extends Command
{
    protected $signature = 'agentic:make-code-tool
                            {name : Tool class name, e.g. LookupOrder}
                            {--handler= : Handler id, e.g. myapp.orders.lookup}';

    protected $description = 'Create a custom code tool class in the host app';

    public function handle(Filesystem $files): int
    {
        $class = Str::studly($this->argument('name'));
        $class = Str::replaceLast('Tool', '', $class).'Tool';

        $namespace = (string) config('agentic.code_tools.namespace', 'App\\Agentic\\Tools\\Custom');
        $path = (string) (config('agentic.code_tools.paths.0') ?? app_path('Agentic/Tools/Custom'));

        if ($namespace === '' || $path === '') {
            $this->error('Set agentic.code_tools.namespace and agentic.code_tools.paths in config/agentic.php');

            return self::FAILURE;
        }

        if (! is_dir($path)) {
            $files->makeDirectory($path, 0755, true);
        }

        $handler = $this->option('handler')
            ?: Str::snake(Str::replaceLast('Tool', '', $class));

        $stub = $files->get(__DIR__.'/../../stubs/CodeToolHandler.stub');
        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ handler }}', '{{ description }}'],
            [$namespace, $class, $handler, Str::headline(Str::replaceLast('Tool', '', $class))],
            $stub,
        );

        $target = $path.'/'.$class.'.php';

        if ($files->exists($target)) {
            $this->error("File already exists: {$target}");

            return self::FAILURE;
        }

        $files->put($target, $contents);
        $this->info("Created {$target}");
        $this->line('Run: php artisan agentic:code-tools-sync (or open Admin → Custom code tools → Sync)');

        return self::SUCCESS;
    }
}
