<?php

namespace Agentic\Console;

use Agentic\Console\Concerns\ConfirmsBeforeSave;
use Agentic\Models\WidgetEmbedToken;
use Agentic\Widget\Embed\WidgetEmbedTokenService;
use Illuminate\Console\Command;

final class WidgetEmbedTokenCommand extends Command
{
    use ConfirmsBeforeSave;

    public function __construct(
        private WidgetEmbedTokenService $tokens,
    ) {
        parent::__construct();
    }

    protected $signature = 'agentic:widget-embed-token
        {action=create : create|list|revoke}
        {--name= : Human label}
        {--agents= : Comma-separated agent slugs (empty = any)}
        {--origins= : Comma-separated allowed origins (https://app.example.com)}
        {--guest : Allow guest X-Agentic-Guest-Id}
        {--no-guest : Disallow guests (Sanctum only if sanctum allowed)}
        {--no-sanctum : Disallow Sanctum; embed token only}
        {--days= : Expire after N days}
        {--id= : Token row id for revoke}';

    protected $description = 'Create or manage widget embed API tokens (wgt_…) for authenticated embed scripts';

    public function handle(): int
    {
        $action = (string) $this->argument('action');

        return match ($action) {
            'create' => $this->createToken(),
            'list' => $this->listTokens(),
            'revoke' => $this->revokeToken(),
            default => $this->invalidAction($action),
        };
    }

    private function createToken(): int
    {
        $interactive = $this->wantsPrompts($this->option('name') !== null ? (string) $this->option('name') : null);
        $name = $interactive
            ? (string) $this->ask('Token name', 'embed-'.now()->format('Y-m-d'))
            : (string) ($this->option('name') ?: 'embed-'.now()->format('Y-m-d'));
        $agents = $interactive
            ? $this->splitCsv((string) $this->ask('Allowed agent slugs (comma, empty = any)', ''))
            : ($this->csvOption('agents') ?? []);
        $origins = $interactive
            ? $this->splitCsv((string) $this->ask('Allowed origins (comma, e.g. https://app.example.com)', ''))
            : ($this->csvOption('origins') ?? []);
        $guest = $interactive
            ? $this->confirm('Allow guest X-Agentic-Guest-Id?', true)
            : ($this->option('no-guest') ? false : true);
        $sanctum = $interactive
            ? $this->confirm('Allow Sanctum as well as the embed token?', true)
            : ! $this->option('no-sanctum');
        $days = $interactive
            ? $this->ask('Expire after N days (empty = never)', '')
            : $this->option('days');

        if ($interactive && ! $this->summarizeAndConfirm([
            ['name', $name],
            ['agents', $agents === [] ? '*' : implode(',', $agents)],
            ['origins', $origins === [] ? '*' : implode(',', $origins)],
            ['guest', $guest ? 'yes' : 'no'],
            ['sanctum', $sanctum ? 'yes' : 'no'],
            ['days', $days === null || $days === '' ? 'never' : (string) $days],
        ], 'Create this embed token?')) {
            return self::SUCCESS;
        }

        $created = $this->tokens->create(
            name: $name,
            allowedAgents: $agents === [] ? null : $agents,
            allowedOrigins: $origins === [] ? null : $origins,
            guestAllowed: $guest,
            sanctumAllowed: $sanctum,
            expiresAt: is_numeric($days) ? now()->addDays((int) $days) : null,
        );
        $plain = $created['plain'];

        $this->components->info('Widget embed token created (store securely — shown once):');
        $this->line($plain);
        $this->newLine();
        $this->line('Enable AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN=true so widget API requires this token.');
        $this->line('Pass to the embed script: embedToken: "'.$plain.'"');

        return self::SUCCESS;
    }

    private function listTokens(): int
    {
        $rows = WidgetEmbedToken::query()->orderByDesc('id')->get();

        if ($rows->isEmpty()) {
            $this->line('No embed tokens.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Prefix', 'Guests', 'Sanctum', 'Agents', 'Enabled', 'Expires'],
            $rows->map(fn (WidgetEmbedToken $t) => [
                $t->id,
                $t->name,
                $t->token_prefix,
                $t->guest_allowed ? 'yes' : 'no',
                $t->sanctum_allowed ? 'yes' : 'no',
                $t->allowed_agents ? implode(',', $t->allowed_agents) : '*',
                $t->enabled ? 'yes' : 'no',
                $t->expires_at?->toDateString() ?? '—',
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function revokeToken(): int
    {
        $id = $this->option('id');
        if (! is_numeric($id) && ! $this->option('no-interaction')) {
            $id = $this->ask('Token row id to revoke');
        }
        if (! is_numeric($id)) {
            $this->error('--id= required for revoke');

            return self::FAILURE;
        }

        $token = WidgetEmbedToken::query()->find((int) $id);
        if ($token === null) {
            $this->error('Token not found');

            return self::FAILURE;
        }

        if (! $this->option('no-interaction') && ! $this->confirm('Revoke embed token ['.$token->name.']?', false)) {
            return self::SUCCESS;
        }

        $token->forceFill(['enabled' => false])->save();
        $this->info('Token disabled.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>|null
     */
    private function csvOption(string $key): ?array
    {
        $raw = $this->option($key);
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function invalidAction(string $action): int
    {
        $this->error("Unknown action: {$action}");

        return self::FAILURE;
    }
}
