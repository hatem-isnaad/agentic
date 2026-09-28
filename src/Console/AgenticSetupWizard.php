<?php

namespace Agentic\Console;

use Agentic\Console\Support\EnvFileWriter;
use Illuminate\Console\Command;

final class AgenticSetupWizard
{
    public function __construct(private EnvFileWriter $envWriter) {}

    public function run(Command $command): int
    {
        $command->components->info('Agentic setup wizard');
        $command->line('Use arrow keys / numbers to choose options. Press Enter for defaults.');
        $command->newLine();

        $mode = $command->choice('Deployment mode (routes + auth)', [
            'local' => 'Laptop — admin + widget demo, open admin API',
            'production' => 'Server — admin + widget, Sanctum + embed token',
            'widget' => 'Server — widget API only (no admin)',
        ], 'local');

        $command->newLine();
        $command->components->twoColumnDetail('AI provider', '');

        $provider = $command->choice('Default AI provider for agents', [
            'ollama' => 'Ollama (local)',
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'gemini' => 'Google Gemini',
        ], config('agentic.ai.provider') ?: 'ollama');

        $models = config("agentic.ai.providers.{$provider}.models", []);
        $models = is_array($models) ? array_values(array_filter($models)) : [];
        $defaultModel = config('agentic.ai.model') ?: ($models[0] ?? 'gpt-4.1-mini');

        if ($models !== []) {
            $model = $command->choice('Default model', array_combine($models, $models), $defaultModel);
        } else {
            $model = $command->ask('Default model name', $defaultModel);
        }

        $env = [
            'AGENTIC_MODE' => $mode,
            'AGENTIC_ENABLED' => 'true',
            'AGENTIC_AI_PROVIDER' => $provider,
            'AGENTIC_AI_MODEL' => (string) $model,
        ];

        $env = array_merge($env, $this->providerCredentials($command, $provider));

        if ($command->confirm('Configure knowledge / RAG (document search)?', true)) {
            $embedding = $command->choice('Embedding driver', [
                'laravel_ai' => 'Real embeddings via Laravel AI (recommended)',
                'deterministic' => 'Fake vectors (tests / no GPU)',
            ], 'laravel_ai');

            $env['AGENTIC_KNOWLEDGE_EMBEDDING'] = $embedding;

            if ($embedding === 'laravel_ai') {
                $embedProvider = $command->choice('Embedding provider', [
                    'ollama' => 'Ollama',
                    'openai' => 'OpenAI',
                    'anthropic' => 'Anthropic',
                    'gemini' => 'Gemini',
                ], $provider === 'ollama' ? 'ollama' : $provider);
                $env['AGENTIC_KNOWLEDGE_EMBEDDING_PROVIDER'] = $embedProvider;
                $env['AGENTIC_KNOWLEDGE_EMBEDDING_MODEL'] = $command->ask(
                    'Embedding model',
                    $embedProvider === 'ollama' ? 'mxbai-embed-large' : 'text-embedding-3-small',
                );
            }

            $vector = $command->choice('Vector store', [
                'pgvector' => 'Postgres + pgvector',
                'array' => 'In-memory (dev only)',
                'pinecone' => 'Pinecone cloud',
            ], 'pgvector');
            $env['AGENTIC_VECTOR_STORE'] = $vector;

            if ($vector === 'pgvector') {
                $dims = $command->ask('Vector dimensions (mxbai-embed-large = 1024)', '1024');
                $env['AGENTIC_PGVECTOR_DIMENSIONS'] = (string) $dims;
            }
        }

        $env['AGENTIC_PERMISSION_DEFAULT'] = 'deny';

        if ($mode === 'production') {
            $env['AGENTIC_ADMIN_GATE'] = $command->ask('Admin gate name (Gate::define in AppServiceProvider)', 'viewAgentic');
        }

        if ($mode !== 'widget') {
            $broadcast = $command->choice('Widget realtime driver', [
                'polling' => 'Simple HTTP polling (no extra services)',
                'pusher' => 'Pusher (needs queue worker)',
            ], 'polling');

            $env['AGENTIC_WIDGET_BROADCAST_DRIVER'] = $broadcast;

            if ($broadcast === 'pusher') {
                $env['PUSHER_APP_ID'] = $command->ask('PUSHER_APP_ID', '');
                $env['PUSHER_APP_KEY'] = $command->ask('PUSHER_APP_KEY', '');
                $env['PUSHER_APP_SECRET'] = $command->secret('PUSHER_APP_SECRET') ?: '';
                $env['PUSHER_APP_CLUSTER'] = $command->ask('PUSHER_APP_CLUSTER', 'mt1');
            }
        }

        $themePresets = config('agentic.widget.embed.theme_presets', ['aurora', 'midnight', 'ocean', 'isnaad']);
        if (is_array($themePresets) && $themePresets !== []) {
            $labels = array_combine($themePresets, $themePresets);
            $env['AGENTIC_WIDGET_THEME'] = $command->choice(
                'Widget color preset',
                $labels,
                config('agentic.widget.theme.default', 'aurora'),
            );
        }

        $command->newLine();
        $command->components->info('Writing .env …');

        try {
            $appended = $this->envWriter->merge($env);
        } catch (\RuntimeException $e) {
            $command->components->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($appended !== []) {
            $command->line('Added keys: '.implode(', ', $appended));
        } else {
            $command->line('Updated existing Agentic keys in .env');
        }

        $command->newLine();
        $command->components->twoColumnDetail('AGENTIC_MODE', $mode);
        $command->line('Run: php artisan config:clear');

        if ($command->confirm('Publish Agentic admin + widget assets now?', true)) {
            $command->call('vendor:publish', ['--tag' => 'agentic-admin-assets', '--force' => true]);
            $command->call('vendor:publish', ['--tag' => 'agentic-widget-assets', '--force' => true]);
        }

        if ($command->confirm('Run database migrations now?', true)) {
            $command->call('migrate', ['--force' => true]);
        }

        if ($command->confirm('Create a widget embed token (wgt_…) in the database?', $mode !== 'local')) {
            $origin = $command->ask('Allowed origin (e.g. http://localhost:8000)', 'http://localhost:8000');
            $command->call('agentic:widget-embed-token', [
                'action' => 'create',
                '--name' => 'install',
                '--origins' => $origin,
                '--guest' => true,
            ]);
        }

        $command->newLine();
        $command->components->info('Setup complete.');
        $command->line('Admin (local/production): /'.trim((string) config('agentic.admin.web.prefix', 'agentic/admin'), '/'));
        $command->line('Docs: vendor/hatem-isnaad/agentic/docs/INSTALL_WIZARD.md');

        return Command::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function providerCredentials(Command $command, string $provider): array
    {
        return match ($provider) {
            'ollama' => [
                'OLLAMA_URL' => $command->ask('Ollama URL', 'http://localhost:11434'),
            ],
            'openai' => [
                'OPENAI_API_KEY' => $command->secret('OPENAI_API_KEY') ?: '',
            ],
            'anthropic' => [
                'ANTHROPIC_API_KEY' => $command->secret('ANTHROPIC_API_KEY') ?: '',
            ],
            'gemini' => [
                'GEMINI_API_KEY' => $command->secret('GEMINI_API_KEY') ?: '',
            ],
            default => [],
        };
    }
}
