<?php

namespace Agentic\Connections;

use Agentic\Contracts\Connections\ConnectionResolver;
use Agentic\Models\Connection;

final class EloquentConnectionResolver implements ConnectionResolver
{
    public function resolve(string|int $connection): ?Connection
    {
        $query = Connection::query();

        if (is_numeric($connection)) {
            return $query->find((int) $connection);
        }

        return $query->where('slug', $connection)->first();
    }
}
