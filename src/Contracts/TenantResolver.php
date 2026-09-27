<?php

namespace Agentic\Contracts;

use Illuminate\Http\Request;

/**
 * Host applications may bind a custom resolver to map HTTP requests (or other
 * subjects) to a tenant identifier for memory, RAG, and conversation scoping.
 */
interface TenantResolver
{
    public function resolve(?Request $request = null): string|int|null;
}
