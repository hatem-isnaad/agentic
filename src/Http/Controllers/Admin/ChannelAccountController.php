<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Channels\ChannelAccountService;
use Agentic\Channels\ChannelDriver;
use Agentic\Channels\ChannelKind;
use Agentic\Http\Requests\Admin\StoreChannelAccountRequest;
use Agentic\Http\Requests\Admin\UpdateChannelAccountRequest;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Models\ChannelAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class ChannelAccountController
{
    public function __construct(private ChannelAccountService $accounts) {}

    public function options(): JsonResponse
    {
        $channels = [];
        foreach (ChannelKind::cases() as $kind) {
            $channels[] = [
                'channel' => $kind->value,
                'drivers' => array_map(fn (ChannelDriver $driver) => $driver->value, ChannelDriver::allowedFor($kind)),
            ];
        }

        return JsonApiResponse::data($channels);
    }

    public function index(Request $request): JsonResponse
    {
        $rows = ChannelAccount::query()->orderByDesc('id')->get()->map(fn (ChannelAccount $row) => $this->accounts->toAdminArray($row))->all();

        return response()->json(AdminPaginator::paginate($rows, $request));
    }

    public function store(StoreChannelAccountRequest $request): JsonResponse
    {
        try {
            $account = $this->accounts->create($request->validated());
        } catch (InvalidArgumentException $e) {
            return JsonApiResponse::error($e->getMessage(), 422);
        }

        return JsonApiResponse::created($this->accounts->toAdminArray($account));
    }

    public function show(int $id): JsonResponse
    {
        $account = ChannelAccount::query()->findOrFail($id);

        return JsonApiResponse::data($this->accounts->toAdminArray($account));
    }

    public function update(UpdateChannelAccountRequest $request, int $id): JsonResponse
    {
        try {
            $account = $this->accounts->update(ChannelAccount::query()->findOrFail($id), $request->validated());
        } catch (InvalidArgumentException $e) {
            return JsonApiResponse::error($e->getMessage(), 422);
        }

        return JsonApiResponse::data($this->accounts->toAdminArray($account));
    }

    public function destroy(int $id): JsonResponse
    {
        ChannelAccount::query()->whereKey($id)->delete();

        return JsonApiResponse::data(['deleted' => true]);
    }
}
