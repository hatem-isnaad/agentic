<?php

namespace Agentic\Channels;

use Agentic\Models\ChannelAccount;
use InvalidArgumentException;

final class ChannelAccountService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChannelAccount
    {
        $this->assertDriver($data);

        return ChannelAccount::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ChannelAccount $account, array $data): ChannelAccount
    {
        $merged = array_merge($account->only(['channel', 'driver']), $data);
        $this->assertDriver($merged);
        $account->fill($data);
        $account->save();

        return $account->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(ChannelAccount $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'slug' => $account->slug,
            'channel' => $account->channel,
            'driver' => $account->driver,
            'agent_slug' => $account->agent_slug,
            'external_id' => $account->external_id,
            'display_number' => $account->display_number,
            'status' => $account->status,
            'config' => $account->config,
            'has_credentials' => $account->getRawOriginal('credentials') !== null,
            'created_at' => optional($account->created_at)?->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertDriver(array $data): void
    {
        $channel = ChannelKind::tryFrom((string) ($data['channel'] ?? ''));
        $driver = ChannelDriver::tryFrom((string) ($data['driver'] ?? ''));
        if ($channel === null || $driver === null) {
            throw new InvalidArgumentException('Invalid channel or driver.');
        }
        if (! in_array($driver, ChannelDriver::allowedFor($channel), true)) {
            throw new InvalidArgumentException('Driver ['.$driver->value.'] is not allowed for channel ['.$channel->value.'].');
        }
    }
}
