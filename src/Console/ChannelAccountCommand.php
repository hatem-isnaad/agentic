<?php

namespace Agentic\Console;

use Agentic\Channels\ChannelAccountService;
use Agentic\Channels\ChannelDriver;
use Agentic\Channels\ChannelKind;
use Agentic\Models\ChannelAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ChannelAccountCommand extends Command
{
    protected $signature = 'agentic:channel-account
        {action=create : create|list|delete}
        {--name=}
        {--slug=}
        {--channel= : widget|whatsapp|messenger}
        {--driver= : embed|meta_cloud|webjs}
        {--agent=}
        {--external-id=}
        {--id=}';

    protected $description = 'Create or list messaging channel accounts (widget / WhatsApp)';

    public function __construct(private ChannelAccountService $accounts)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ((string) $this->argument('action')) {
            'create' => $this->createAccount(),
            'list' => $this->listAccounts(),
            'delete' => $this->deleteAccount(),
            default => $this->invalid(),
        };
    }

    private function createAccount(): int
    {
        $interactive = ! $this->option('no-interaction') && $this->option('name') === null;
        $channel = $interactive ? (string) $this->choice('Channel', ChannelKind::values(), 1) : (string) ($this->option('channel') ?: 'whatsapp');
        $kind = ChannelKind::tryFrom($channel) ?? ChannelKind::WhatsApp;
        $drivers = array_map(fn (ChannelDriver $driver) => $driver->value, ChannelDriver::allowedFor($kind));
        $driver = $interactive ? (string) $this->choice('Driver', $drivers, 0) : (string) ($this->option('driver') ?: $drivers[0]);
        $name = $interactive ? (string) $this->ask('Name', 'Sales WhatsApp') : (string) $this->option('name');
        $slug = $interactive ? (string) $this->ask('Slug', Str::slug($name)) : (string) ($this->option('slug') ?: Str::slug((string) $name));
        $agent = $interactive ? (string) $this->ask('Agent slug', 'support') : (string) ($this->option('agent') ?: '');
        $external = $interactive ? (string) $this->ask('External id (phone_number_id or session)', '') : (string) ($this->option('external-id') ?: '');

        $credentials = [];
        $config = [];
        if ($interactive && $driver === 'meta_cloud') {
            $credentials['access_token'] = (string) $this->ask('Meta access token (or env:KEY)', '');
            $credentials['app_secret'] = (string) $this->ask('Meta app secret', '');
            $credentials['verify_token'] = (string) $this->ask('Webhook verify token', '');
        }
        if ($interactive && $driver === 'webjs') {
            $config['sidecar_url'] = (string) $this->ask('Sidecar URL', '');
            $credentials['sidecar_secret'] = (string) $this->ask('Sidecar secret', '');
        }

        $this->table(['Field', 'Value'], [
            ['name', $name],
            ['slug', $slug],
            ['channel', $channel],
            ['driver', $driver],
            ['agent', $agent],
            ['external_id', $external],
        ]);

        if ($interactive && ! $this->confirm('Save this channel account?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        try {
            $row = $this->accounts->create(array_filter([
                'name' => $name,
                'slug' => $slug,
                'channel' => $channel,
                'driver' => $driver,
                'agent_slug' => $agent !== '' ? $agent : null,
                'external_id' => $external !== '' ? $external : null,
                'config' => $config !== [] ? $config : null,
                'credentials' => $credentials !== [] ? $credentials : null,
                'status' => 'active',
            ], fn ($value) => $value !== null));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Saved channel account ['.$row->slug.'].');

        return self::SUCCESS;
    }

    private function listAccounts(): int
    {
        $rows = ChannelAccount::query()->orderBy('slug')->get();
        if ($rows->isEmpty()) {
            $this->line('No channel accounts.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'slug', 'channel', 'driver', 'external_id'],
            $rows->map(fn (ChannelAccount $row) => [$row->id, $row->slug, $row->channel, $row->driver, $row->external_id])->all(),
        );

        return self::SUCCESS;
    }

    private function deleteAccount(): int
    {
        $id = $this->option('id') ?: $this->ask('Channel account id');
        $row = ChannelAccount::query()->find($id);
        if ($row === null) {
            $this->error('Not found.');

            return self::FAILURE;
        }
        if (! $this->option('no-interaction') && ! $this->confirm('Delete ['.$row->slug.']?', false)) {
            return self::SUCCESS;
        }
        $row->delete();
        $this->components->info('Deleted.');

        return self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('Action must be create, list, or delete.');

        return self::FAILURE;
    }
}
