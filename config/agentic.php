<?php

return [
    'enabled' => env('AGENTIC_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default AI provider / model (Laravel AI SDK)
    |--------------------------------------------------------------------------
    |
    | Used when an AgentDefinition does not specify provider or model.
    | Agentic never talks to providers directly — Laravel AI SDK does.
    |
    */
    'ai' => [
        'provider' => env('AGENTIC_AI_PROVIDER'),
        'model' => env('AGENTIC_AI_MODEL'),
        'deferred_tools' => [
            'enabled' => env('AGENTIC_DEFERRED_TOOLS', false),
            'deferred_count' => env('AGENTIC_DEFERRED_TOOL_COUNT', 10),
            'direct_tools' => env('AGENTIC_DIRECT_TOOL_COUNT', 1),
            'strategy' => env('AGENTIC_DEFERRED_TOOL_STRATEGY'),
        ],
    ],

    'approvals' => [
        'default' => env('AGENTIC_TOOL_APPROVAL_DEFAULT', 'never'),
        'reason' => env(
            'AGENTIC_TOOL_APPROVAL_REASON',
            'This tool requires human approval before execution.',
        ),
    ],

    'skill_routing' => [
        'enabled' => env('AGENTIC_SKILL_ROUTING', true),
        'limit' => env('AGENTIC_SKILL_ROUTING_LIMIT', 3),
        'ai' => [
            'enabled' => env('AGENTIC_AI_SKILL_ROUTING', false),
            'provider' => env('AGENTIC_AI_SKILL_ROUTING_PROVIDER'),
            'model' => env('AGENTIC_AI_SKILL_ROUTING_MODEL'),
            'min_confidence' => env('AGENTIC_AI_SKILL_ROUTING_MIN_CONFIDENCE', 0.75),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission denial
    |--------------------------------------------------------------------------
    |
    | default: deny | allow
    | Denied tools never reach a ToolDriver.
    |
    */
    'permissions' => [
        'default' => env('AGENTIC_PERMISSION_DEFAULT', 'deny'),
        'checker' => Agentic\Permission\DenyAllPermissionChecker::class,
        'denial_message' => 'Permission denied for tool [:tool].',
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution tracking
    |--------------------------------------------------------------------------
    |
    | driver: eloquent | memory
    |
    */
    'execution' => [
        'driver' => env('AGENTIC_EXECUTION_DRIVER', 'eloquent'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversations
    |--------------------------------------------------------------------------
    |
    | Agentic conversation records associate agent/user/tenant metadata.
    | Laravel AI SDK remains responsible for provider-level message history.
    |
    | driver: eloquent | memory
    |
    */
    'conversation' => [
        'driver' => env('AGENTIC_CONVERSATION_DRIVER', 'eloquent'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */
    'routing' => [
        'fallback_agent' => env('AGENTIC_FALLBACK_AGENT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge
    |--------------------------------------------------------------------------
    |
    | driver: eloquent | memory
    |
    */
    'knowledge' => [
        'driver' => env('AGENTIC_KNOWLEDGE_DRIVER', 'eloquent'),
        'embedding' => env('AGENTIC_KNOWLEDGE_EMBEDDING', 'null'),
        'embedding_provider' => env('AGENTIC_KNOWLEDGE_EMBEDDING_PROVIDER'),
        'embedding_model' => env('AGENTIC_KNOWLEDGE_EMBEDDING_MODEL'),
        'vector_store' => env('AGENTIC_VECTOR_STORE', 'array'),
        'pinecone' => [
            'host' => env('PINECONE_HOST'),
            'api_key' => env('PINECONE_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament admin (optional)
    |--------------------------------------------------------------------------
    |
    | When filament/filament is installed, register the Agentic plugin on panels
    | listed here (panel IDs). Leave empty to skip auto-registration.
    |
    */
    'filament' => [
        'panels' => array_filter(explode(',', (string) env('AGENTIC_FILAMENT_PANELS', 'admin'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP API (optional)
    |--------------------------------------------------------------------------
    |
    | When enabled, registers JSON routes for agents, routing, executions,
    | and conversations. Host apps should apply their own authentication.
    |
    */
    'api' => [
        'enabled' => env('AGENTIC_API_ENABLED', false),
        'prefix' => env('AGENTIC_API_PREFIX', 'api/agentic'),
        'middleware' => ['api'],
    ],

    'http' => [
        'allow_private_hosts' => env('AGENTIC_HTTP_ALLOW_PRIVATE_HOSTS', false),
        'allow_unresolved_hosts' => env('AGENTIC_HTTP_ALLOW_UNRESOLVED_HOSTS', false),
        'allow_redirects' => env('AGENTIC_HTTP_ALLOW_REDIRECTS', false),
        'max_response_bytes' => (int) env('AGENTIC_HTTP_MAX_RESPONSE_BYTES', 5 * 1024 * 1024),
        'max_request_body_bytes' => (int) env('AGENTIC_HTTP_MAX_REQUEST_BODY_BYTES', 1024 * 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool drivers
    |--------------------------------------------------------------------------
    |
    | Map driver names to ToolDriver implementations. Register custom drivers
    | here or via DriverResolver::extend().
    |
    */
    'tool_drivers' => [
        'http' => Agentic\Tool\Drivers\HttpToolDriver::class,
        'code' => Agentic\Tool\Drivers\CodeToolDriver::class,
        'mcp' => Agentic\Tool\Drivers\McpToolDriver::class,
    ],
];
