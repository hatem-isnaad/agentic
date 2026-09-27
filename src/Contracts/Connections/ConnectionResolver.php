<?php

namespace Agentic\Contracts\Connections;

use Agentic\Models\Connection;

interface ConnectionResolver
{
    public function resolve(string|int $connection): ?Connection;
}
