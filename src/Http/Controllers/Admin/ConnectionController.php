<?php

namespace Agentic\Http\Controllers\Admin;

use Agentic\Connections\ConnectionService;
use Agentic\Connections\OAuth2TokenManager;
use Agentic\Http\Requests\Admin\StoreConnectionRequest;
use Agentic\Http\Requests\Admin\UpdateConnectionRequest;
use Agentic\Http\Responses\JsonApiResponse;
use Agentic\Http\Support\AdminPaginator;
use Agentic\Models\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

final class ConnectionController
{
    public function __construct(private ConnectionService $connections) {}

    public function index(Request $request): JsonResponse
    {
        $rows = Connection::query()->orderByDesc('id')->get()->map(fn (Connection $row) => $this->connections->toAdminArray($row))->all();

        return response()->json(AdminPaginator::paginate($rows, $request));
    }

    public function store(StoreConnectionRequest $request): JsonResponse
    {
        try {
            $row = $this->connections->create($this->payload($request->validated()));
        } catch (InvalidArgumentException $e) {
            return JsonApiResponse::error($e->getMessage(), 422);
        }

        return JsonApiResponse::created($this->connections->toAdminArray($row));
    }

    public function show(int $id): JsonResponse
    {
        return JsonApiResponse::data($this->connections->toAdminArray(Connection::query()->findOrFail($id)));
    }

    public function update(UpdateConnectionRequest $request, int $id): JsonResponse
    {
        try {
            $row = $this->connections->update(Connection::query()->findOrFail($id), $this->payload($request->validated()));
        } catch (InvalidArgumentException $e) {
            return JsonApiResponse::error($e->getMessage(), 422);
        }

        return JsonApiResponse::data($this->connections->toAdminArray($row));
    }

    public function destroy(int $id): JsonResponse
    {
        Connection::query()->whereKey($id)->delete();

        return JsonApiResponse::data(['deleted' => true]);
    }

    public function refresh(int $id): JsonResponse
    {
        $row = Connection::query()->findOrFail($id);
        try {
            app(OAuth2TokenManager::class)->refresh($row);
        } catch (Throwable $e) {
            return JsonApiResponse::error($e->getMessage(), 422);
        }

        return JsonApiResponse::data($this->connections->toAdminArray($row->fresh()));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        if (isset($validated['name_key'])) {
            $validated['config'] = array_merge($validated['config'] ?? [], ['name' => $validated['name_key']]);
            unset($validated['name_key']);
        }

        return $validated;
    }
}
