<?php

namespace Agentic\Http\Support;

use Illuminate\Http\Request;

final class AdminPaginator
{
    /**
     * @param  list<mixed>  $items
     * @return array{data: list<mixed>, meta: array<string, mixed>}
     */
    public static function paginate(array $items, Request $request): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $query = strtolower(trim((string) $request->query('q', '')));
        $status = $request->query('status');

        $filtered = array_values(array_filter($items, function ($item) use ($query, $status): bool {
            if ($status !== null && $status !== '') {
                $itemStatus = is_array($item) ? ($item['status'] ?? null) : (is_object($item) && property_exists($item, 'status') ? $item->status : null);
                if ((string) $itemStatus !== (string) $status) {
                    return false;
                }
            }

            if ($query === '') {
                return true;
            }

            $haystack = strtolower(json_encode($item, JSON_THROW_ON_ERROR));

            return str_contains($haystack, $query);
        }));

        $total = count($filtered);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($filtered, $offset, $perPage);

        return [
            'data' => $slice,
            'meta' => AdminLocaleMeta::build([
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ]),
        ];
    }
}
