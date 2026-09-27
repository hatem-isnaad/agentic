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
        'providers' => [
            'openai' => [
                'label' => 'OpenAI',
                'models' => array_filter(explode(',', (string) env('AGENTIC_OPENAI_MODELS', 'gpt-4.1-mini,gpt-4o'))),
            ],
            'anthropic' => [
                'label' => 'Anthropic',
                'models' => array_filter(explode(',', (string) env('AGENTIC_ANTHROPIC_MODELS', 'claude-sonnet-4-20250514'))),
            ],
            'gemini' => [
                'label' => 'Google Gemini',
                'models' => array_filter(explode(',', (string) env(
                    'AGENTIC_GEMINI_MODELS',
                    'gemini-3.6-flash,gemini-3.1-flash-lite',
                ))),
            ],
            'ollama' => [
                'label' => 'Ollama (local)',
                'models' => array_filter(explode(',', (string) env(
                    'AGENTIC_OLLAMA_MODELS',
                    'qwen3.5:4b,llama3.2,nomic-embed-text',
                ))),
            ],
        ],
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

    'tool_approval' => [
        'enabled' => env('AGENTIC_TOOL_APPROVAL_ENABLED', true),
        'tool_patterns' => array_filter(explode(',', (string) env(
            'AGENTIC_TOOL_APPROVAL_PATTERNS',
            '*.write,*.create,*.update,*.delete',
        ))),
        'auto_execute_on_approve' => env('AGENTIC_TOOL_APPROVAL_AUTO_EXECUTE', true),
        'auto_resume_after_execute' => env('AGENTIC_TOOL_APPROVAL_AUTO_RESUME', true),
    ],

    'auth' => [
        'enabled' => env('AGENTIC_AUTH_ENABLED', true),
        'prefix' => env('AGENTIC_AUTH_PREFIX', 'api/agentic/auth'),
        'route_name_prefix' => 'agentic.auth.',
        'middleware' => ['api'],
        'protect' => [
            'admin_api' => env('AGENTIC_ADMIN_REQUIRE_AUTH', false),
            'runtime_api' => env('AGENTIC_API_REQUIRE_AUTH', false),
        ],
        'sanctum' => [
            'stateful_widget' => env('AGENTIC_AUTH_STATEFUL_WIDGET', true),
            'issue_token_on_passkey_login' => env('AGENTIC_AUTH_ISSUE_TOKEN_ON_PASSKEY_LOGIN', true),
            'token_name' => env('AGENTIC_AUTH_TOKEN_NAME', 'agentic'),
            'token_abilities' => ['*'],
        ],
    ],

    'admin' => [
        'enabled' => env('AGENTIC_ADMIN_ENABLED', true),
        'api' => [
            'enabled' => env('AGENTIC_ADMIN_API_ENABLED', true),
            'prefix' => env('AGENTIC_ADMIN_API_PREFIX', 'api/agentic/admin'),
            'middleware' => [
                'api',
                Agentic\Http\Middleware\SetAdminLocale::class,
            ],
            'route_name_prefix' => 'agentic.admin.',
        ],
        'default_locale' => env('AGENTIC_ADMIN_LOCALE', 'en'),
        'locales' => ['en', 'ar'],
        'rtl_locales' => ['ar'],
    ],

    'widget' => [
        'enabled' => env('AGENTIC_WIDGET_ENABLED', true),
        'prefix' => env('AGENTIC_WIDGET_PREFIX', 'api/agentic/widget'),
        'middleware' => [
            'api',
            Agentic\Http\Middleware\SetAdminLocale::class,
            Agentic\Http\Middleware\EnsureWidgetAccess::class,
        ],
        'route_name_prefix' => 'agentic.widget.',
        'auth' => [
            'mode' => env('AGENTIC_WIDGET_AUTH_MODE', 'both'),
            'allow_guest' => env('AGENTIC_WIDGET_ALLOW_GUEST', true),
            'allow_authenticated' => env('AGENTIC_WIDGET_ALLOW_AUTH', true),
        ],
        'conversation' => [
            'max_open_per_user' => (int) env('AGENTIC_WIDGET_MAX_CONVERSATIONS', 10),
        ],
        'intake' => [
            'enabled' => env('AGENTIC_WIDGET_INTAKE_ENABLED', false),
            'welcome_message' => env('AGENTIC_WIDGET_WELCOME_MESSAGE'),
            'questions' => [],
        ],
        'locale' => [
            'default' => env('AGENTIC_WIDGET_LOCALE', 'en'),
            'supported' => ['en', 'ar'],
            'agent_language' => env('AGENTIC_WIDGET_AGENT_LANGUAGE'),
        ],
        'theme' => [
            'default' => env('AGENTIC_WIDGET_THEME', 'light'),
            'direction' => env('AGENTIC_WIDGET_THEME_DIRECTION', 'auto'),
            'custom' => [],
            'sounds' => [],
        ],
        'reply' => [
            'formats' => ['text', 'html', 'table', 'list', 'card', 'code', 'blocks', 'actions'],
        ],
        'broadcast' => [
            'driver' => env('AGENTIC_WIDGET_BROADCAST_DRIVER', 'polling'),
            'channel_prefix' => env('AGENTIC_WIDGET_BROADCAST_PREFIX', 'agentic-widget'),
            'polling' => [
                'interval_ms' => (int) env('AGENTIC_WIDGET_BROADCAST_POLL_MS', 3000),
            ],
            'pusher' => [
                'key' => env('PUSHER_APP_KEY'),
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
            ],
            'socketio' => [
                'url' => env('AGENTIC_WIDGET_SOCKETIO_URL'),
                'token' => env('AGENTIC_WIDGET_SOCKETIO_TOKEN'),
                'timeout' => (int) env('AGENTIC_WIDGET_SOCKETIO_TIMEOUT', 5),
            ],
        ],
        'agents' => [],
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
        'checker' => env('AGENTIC_PERMISSION_CHECKER', Agentic\Permission\DenyAllPermissionChecker::class),
        'denial_message' => env('AGENTIC_PERMISSION_DENIAL_MESSAGE', 'Permission denied for tool [:tool].'),
        'allow_patterns' => array_filter(explode(',', (string) env('AGENTIC_PERMISSION_ALLOW_PATTERNS', ''))),
        'deny_patterns' => array_filter(explode(',', (string) env('AGENTIC_PERMISSION_DENY_PATTERNS', ''))),
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
        'embedding_provider' => env('AGENTIC_KNOWLEDGE_EMBEDDING_PROVIDER', env('AGENTIC_AI_PROVIDER', 'openai')),
        'embedding_model' => env('AGENTIC_KNOWLEDGE_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'vector_store' => env('AGENTIC_VECTOR_STORE', 'array'),
        'pgvector' => [
            'dimensions' => (int) env('AGENTIC_PGVECTOR_DIMENSIONS', 1536),
        ],
        'pinecone' => [
            'host' => env('PINECONE_HOST'),
            'api_key' => env('PINECONE_API_KEY'),
            'dimensions' => (int) env('AGENTIC_PINECONE_DIMENSIONS', env('AGENTIC_PGVECTOR_DIMENSIONS', 1536)),
        ],
        'chunk_size' => (int) env('AGENTIC_KNOWLEDGE_CHUNK_SIZE', 800),
        'chunk_overlap' => (int) env('AGENTIC_KNOWLEDGE_CHUNK_OVERLAP', 120),
        'fetch' => [
            'enabled' => env('AGENTIC_KNOWLEDGE_URL_FETCH', true),
            'timeout' => (int) env('AGENTIC_KNOWLEDGE_URL_FETCH_TIMEOUT', 15),
            'max_bytes' => (int) env('AGENTIC_KNOWLEDGE_URL_FETCH_MAX_BYTES', 5 * 1024 * 1024),
        ],
        'queue_reindex' => env('AGENTIC_KNOWLEDGE_QUEUE_REINDEX', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-tenant resolution
    |--------------------------------------------------------------------------
    |
    | Bind Agentic\Contracts\TenantResolver in the host app to customize tenant
    | detection. Defaults read X-Agentic-Tenant-Id and the authenticated user's
    | tenant_id attribute.
    |
    */
    'tenant' => [
        'header' => env('AGENTIC_TENANT_HEADER', 'X-Agentic-Tenant-Id'),
        'user_attribute' => env('AGENTIC_TENANT_USER_ATTRIBUTE', 'tenant_id'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory
    |--------------------------------------------------------------------------
    |
    | Scoped long-term facts injected into agent context (user, conversation,
    | agent, tenant). Opt-in per host app via API or MemoryManager.
    |
    | driver: eloquent | memory
    |
    */
    'memory' => [
        'enabled' => env('AGENTIC_MEMORY_ENABLED', true),
        'driver' => env('AGENTIC_MEMORY_DRIVER', 'eloquent'),
        'max_context_entries' => (int) env('AGENTIC_MEMORY_MAX_CONTEXT_ENTRIES', 20),
        'default_ttl_days' => env('AGENTIC_MEMORY_DEFAULT_TTL_DAYS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflows
    |--------------------------------------------------------------------------
    |
    | driver: eloquent | memory
    |
    */
    'workflows' => [
        'enabled' => env('AGENTIC_WORKFLOWS_ENABLED', true),
        'driver' => env('AGENTIC_WORKFLOW_DRIVER', 'eloquent'),
        'max_steps' => (int) env('AGENTIC_WORKFLOW_MAX_STEPS', 100),
        'max_parallel_branches' => (int) env('AGENTIC_WORKFLOW_MAX_PARALLEL_BRANCHES', 10),
        'runs' => [
            'retention_days' => (int) env('AGENTIC_WORKFLOW_RUN_RETENTION_DAYS', 90),
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
        'rate_limit' => [
            'enabled' => env('AGENTIC_API_RATE_LIMIT_ENABLED', true),
            'per_minute' => (int) env('AGENTIC_API_RATE_LIMIT_PER_MINUTE', 120),
        ],
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

    /*
    |--------------------------------------------------------------------------
    | MCP tool discovery
    |--------------------------------------------------------------------------
    |
    | MCP servers are configured in the host app's config/mcp.php (laravel/mcp).
    | Use agentic:mcp-sync or POST /api/agentic/mcp/servers/{server}/sync.
    |
    */
    'mcp' => [
        'enabled' => env('AGENTIC_MCP_ENABLED', true),
        'tool_prefix' => env('AGENTIC_MCP_TOOL_PREFIX', ''),
        'servers' => [],
        'inject_resources' => env('AGENTIC_MCP_INJECT_RESOURCES', true),
        'max_resource_injections' => (int) env('AGENTIC_MCP_MAX_RESOURCE_INJECTIONS', 5),
    ],
];
