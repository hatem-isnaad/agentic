<?php

use Agentic\Http\Middleware\AuthorizeAgenticAdmin;
use Agentic\Http\Middleware\SetAdminLocale;
use Agentic\Http\Middleware\ValidateWidgetEmbed;
use Agentic\Permission\DenyAllPermissionChecker;
use Agentic\Tool\Drivers\CodeToolDriver;
use Agentic\Tool\Drivers\HttpToolDriver;
use Agentic\Tool\Drivers\McpToolDriver;

return [
    'enabled' => env('AGENTIC_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Deployment mode (routes + auth — start here)
    |--------------------------------------------------------------------------
    |
    | AGENTIC_MODE=local       Laptop: admin + widget demo, admin API open (no Sanctum).
    | AGENTIC_MODE=production  Server: admin + widget, Sanctum on admin API, embed token required.
    | AGENTIC_MODE=widget      Server: only /api/agentic/widget/* + embed token (no admin).
    |
    | If unset: local when APP_ENV=local, else production.
    | AGENTIC_WIDGET_ONLY=true is legacy alias for AGENTIC_MODE=widget.
    |
    */
    'deploy' => [
        'mode' => env('AGENTIC_MODE'),
    ],

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
                'models' => array_filter(explode(',', (string) env('AGENTIC_ANTHROPIC_MODELS', 'claude-sonnet-5,claude-sonnet-4-6'))),
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
                    'qwen3:8b,qwen2.5-coder:14b,nomic-embed-text',
                ))),
            ],
        ],
        'deferred_tools' => [
            'enabled' => filter_var(env('AGENTIC_DEFERRED_TOOLS', true), FILTER_VALIDATE_BOOL),
            'deferred_count' => env('AGENTIC_DEFERRED_TOOL_COUNT', 32),
            'direct_tools' => env('AGENTIC_DIRECT_TOOL_COUNT', 8),
            'strategy' => env('AGENTIC_DEFERRED_TOOL_STRATEGY'),
        ],
    ],

    /*
    | Agent voice — stored on the agent as config.persona and injected into instructions.
    | Host PHP: AgentRepository::save([..., 'config' => ['persona' => [...]]])
    */
    'persona' => [
        'genders' => ['male', 'female', 'unspecified'],
        'languages' => ['en', 'ar', 'bilingual'],
        'dialects' => ['msa', 'saudi', 'egyptian', 'gulf', 'levant'],
        'tones' => ['friendly', 'formal', 'casual', 'professional', 'warm'],
        'name_suggestions' => [
            'male' => ['Ahmed', 'Mohamed', 'Omar', 'Khalid', 'Youssef', 'Faisal', 'Hassan'],
            'female' => ['Sara', 'Fatima', 'Noura', 'Layla', 'Mona', 'Hana', 'Reem'],
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
            // Overridden by AGENTIC_MODE presets at boot. Set AGENTIC_MODE= (empty) to use these directly.
            'admin_api' => env('AGENTIC_ADMIN_REQUIRE_AUTH', true),
            'admin_web' => env('AGENTIC_ADMIN_WEB_REQUIRE_AUTH'),
            'runtime_api' => env('AGENTIC_API_REQUIRE_AUTH', true),
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

        /*
        | Who may open admin (after Sanctum when mode=production):
        | AGENTIC_ADMIN_GATE=viewAgentic + Gate::define('viewAgentic', ...) in AppServiceProvider.
        | Local mode skips the gate when APP_ENV=local.
        */
        'authorization' => [
            'gate' => env('AGENTIC_ADMIN_GATE'),
        ],

        'api' => [
            'enabled' => env('AGENTIC_ADMIN_API_ENABLED', true),
            'prefix' => env('AGENTIC_ADMIN_API_PREFIX', 'api/agentic/admin'),
            'middleware' => [
                'api',
                SetAdminLocale::class,
                AuthorizeAgenticAdmin::class,
            ],
            'route_name_prefix' => 'agentic.admin.api.',
            'rate_limit' => [
                'enabled' => filter_var(env('AGENTIC_ADMIN_RATE_LIMIT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                'per_minute' => (int) env('AGENTIC_ADMIN_RATE_LIMIT_PER_MINUTE', 300),
            ],
        ],
        'web' => [
            'enabled' => env('AGENTIC_ADMIN_WEB_ENABLED', false),
            'ui' => env('AGENTIC_ADMIN_WEB_UI', 'spa'),
            'prefix' => env('AGENTIC_ADMIN_PREFIX', 'agentic/admin'),
            'middleware' => [
                'web',
                SetAdminLocale::class,
                AuthorizeAgenticAdmin::class,
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
            SetAdminLocale::class,
            ValidateWidgetEmbed::class,
        ],
        'route_name_prefix' => 'agentic.widget.',
        'web' => [
            'enabled' => env('AGENTIC_WIDGET_WEB_ENABLED', true),
            'prefix' => env('AGENTIC_WIDGET_WEB_PREFIX', 'agentic/widget'),
            'middleware' => ['web'],
            'route_name_prefix' => 'agentic.widget.web.',
        ],
        'auth' => [
            'mode' => env('AGENTIC_WIDGET_AUTH_MODE', 'both'),
            'allow_guest' => filter_var(env('AGENTIC_WIDGET_ALLOW_GUEST', true), FILTER_VALIDATE_BOOLEAN),
            'allow_authenticated' => filter_var(env('AGENTIC_WIDGET_ALLOW_AUTH', true), FILTER_VALIDATE_BOOLEAN),
        ],
        /*
        |--------------------------------------------------------------------------
        | Embeddable widget (standalone JS on Blade / SPA / any site)
        |--------------------------------------------------------------------------
        |
        | When require_token is true, all /api/agentic/widget/* requests must send
        | Authorization: Bearer wgt_… (from agentic:widget-embed-token) or Sanctum.
        | Restrict origins and agents per token. Guest chat still uses X-Agentic-Guest-Id
        | when the token allows guest_allowed.
        |
        */
        'embed' => [
            'require_token' => filter_var(env('AGENTIC_WIDGET_EMBED_REQUIRE_TOKEN', true), FILTER_VALIDATE_BOOLEAN),
            'token' => env('AGENTIC_WIDGET_EMBED_TOKEN'),
            'sanctum_allowed' => filter_var(env('AGENTIC_WIDGET_EMBED_SANCTUM_ALLOWED', true), FILTER_VALIDATE_BOOLEAN),
            'script_url' => env('AGENTIC_WIDGET_EMBED_SCRIPT_URL'),
            'default_agent' => env('AGENTIC_WIDGET_EMBED_DEFAULT_AGENT'),
            'position' => env('AGENTIC_WIDGET_EMBED_POSITION', 'bottom-right'),
            /*
            | Widget colors — set in AgenticChat.init({ theme: 'ocean' }), not in the chat UI.
            | Presets: aurora, midnight, ocean, forest, sunset, rose, gold, arctic, graphite, ember,
            | slate, sand, lime, coral, indigo, mocha, mint, crimson, sky, neon, isnaad, techsup
            | Aliases: light → aurora, dark → midnight, brand → ember, system → aurora|midnight
            | isnaad = Limenos / isnaad.ai brand red #c02526
            | techsup = TechSup Tkt brand plum #6C075D
            */
            'theme_presets' => [
                'aurora', 'midnight', 'ocean', 'forest', 'sunset', 'rose', 'gold', 'arctic', 'graphite', 'ember',
                'slate', 'sand', 'lime', 'coral', 'indigo', 'mocha', 'mint', 'crimson', 'sky', 'neon', 'isnaad', 'techsup',
            ],
            'sound_pack' => env('AGENTIC_WIDGET_SOUND_PACK', 'subtle'),
        ],
        'conversation' => [
            'max_open_per_user' => (int) env('AGENTIC_WIDGET_MAX_CONVERSATIONS', 20),
            'resume_after_hours' => (int) env('AGENTIC_WIDGET_RESUME_HOURS', 24),
        ],
        'history' => [
            'page_size' => (int) env('AGENTIC_WIDGET_HISTORY_PAGE_SIZE', 20),
            'max_page_size' => (int) env('AGENTIC_WIDGET_HISTORY_MAX_PAGE_SIZE', 50),
        ],
        /*
        | Lean agent context per widget message (tokens + latency).
        | Reply UX stays async (HTTP ack + queue + Pusher); execution usage is on the broadcast payload.
        */
        'context' => [
            'enabled' => filter_var(env('AGENTIC_WIDGET_LEAN_CONTEXT', true), FILTER_VALIDATE_BOOL),
            'max_history_messages' => (int) env('AGENTIC_WIDGET_CONTEXT_HISTORY', 8),
            'skill_routing_limit' => (int) env('AGENTIC_WIDGET_SKILL_LIMIT', 2),
            'skills_fallback_limit' => (int) env('AGENTIC_WIDGET_SKILLS_FALLBACK_LIMIT', 2),
            'knowledge_chunk_limit' => (int) env('AGENTIC_WIDGET_KNOWLEDGE_LIMIT', 3),
            'memory_entry_limit' => (int) env('AGENTIC_WIDGET_MEMORY_LIMIT', 5),
            'max_tools' => (int) env('AGENTIC_WIDGET_MAX_TOOLS', 20),
            'compact_skill_descriptions' => filter_var(env('AGENTIC_WIDGET_COMPACT_SKILLS', true), FILTER_VALIDATE_BOOL),
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
        /*
        | null = auto: async when AGENTIC_WIDGET_BROADCAST_DRIVER=pusher (HTTP ack + queue + Pusher).
        | true/false = force async or sync. Async requires: php artisan queue:work
        */
        'async_replies' => env('AGENTIC_WIDGET_ASYNC_REPLIES'),
        'message_batch' => [
            'window_ms' => (int) env('AGENTIC_WIDGET_MESSAGE_BATCH_MS', 0),
            'max_ms' => (int) env('AGENTIC_WIDGET_MESSAGE_BATCH_MAX_MS', 10_000),
        ],
        'stream' => filter_var(env('AGENTIC_WIDGET_STREAM', false), FILTER_VALIDATE_BOOLEAN),
        'handoff' => [
            'enabled' => filter_var(env('AGENTIC_WIDGET_HANDOFF', true), FILTER_VALIDATE_BOOLEAN),
        ],
        'attachments' => [
            'enabled' => filter_var(env('AGENTIC_WIDGET_ATTACHMENTS', true), FILTER_VALIDATE_BOOLEAN),
            'max_files' => (int) env('AGENTIC_WIDGET_ATTACHMENTS_MAX', 3),
            'signed_url_hours' => (int) env('AGENTIC_WIDGET_ATTACHMENTS_SIGNED_HOURS', 12),
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'],
        ],
        'broadcast' => [
            'driver' => env('AGENTIC_WIDGET_BROADCAST_DRIVER', 'polling'),
            'channel_prefix' => env('AGENTIC_WIDGET_BROADCAST_PREFIX', 'agentic-widget'),
            'polling' => [
                'interval_ms' => (int) env('AGENTIC_WIDGET_BROADCAST_POLL_MS', 3000),
            ],
            'pusher' => [
                'app_id' => env('PUSHER_APP_ID'),
                'key' => env('PUSHER_APP_KEY'),
                'secret' => env('PUSHER_APP_SECRET'),
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
            ],
            'socketio' => [
                'url' => env('AGENTIC_WIDGET_SOCKETIO_URL'),
                'token' => env('AGENTIC_WIDGET_SOCKETIO_TOKEN'),
                'timeout' => (int) env('AGENTIC_WIDGET_SOCKETIO_TIMEOUT', 5),
            ],
        ],
        'agents' => [],
        'rate_limit' => [
            'enabled' => filter_var(env('AGENTIC_WIDGET_RATE_LIMIT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'per_minute' => (int) env('AGENTIC_WIDGET_RATE_LIMIT_PER_MINUTE', 60),
        ],
    ],

    /*
    | Channel accounts = messaging connections (widget, WhatsApp, later Messenger).
    | Not the same as agentic_connections (HTTP tool OAuth).
    | WhatsApp: pick driver meta_cloud (live) or webjs (sidecar later). Attach many numbers.
    */
    'channels' => [
        'enabled' => filter_var(env('AGENTIC_CHANNELS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'prefix' => env('AGENTIC_CHANNELS_PREFIX', 'api/agentic/channels'),
        'route_name_prefix' => 'agentic.channels.',
        'middleware' => ['api'],
        'verify_signatures' => filter_var(env('AGENTIC_CHANNELS_VERIFY_SIGNATURES', true), FILTER_VALIDATE_BOOLEAN),
        'whatsapp' => [
            'graph_version' => env('AGENTIC_WHATSAPP_GRAPH_VERSION', 'v21.0'),
            'verify_token' => env('AGENTIC_WHATSAPP_VERIFY_TOKEN'),
            'app_secret' => env('AGENTIC_WHATSAPP_APP_SECRET'),
        ],
        'messenger' => [
            'graph_version' => env('AGENTIC_MESSENGER_GRAPH_VERSION', 'v21.0'),
            'verify_token' => env('AGENTIC_MESSENGER_VERIFY_TOKEN'),
            'app_secret' => env('AGENTIC_MESSENGER_APP_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lean agent context (all channels — admin, API, widget)
    |--------------------------------------------------------------------------
    |
    | One “full system” agent can keep many skills/tools in DB; each turn only
    | loads a routed subset + capped history/RAG/memory. Widget uses stricter
    | limits under agentic.widget.context when AGENTIC_WIDGET_LEAN_CONTEXT=true.
    |
    */
    'context' => [
        'lean_enabled' => filter_var(env('AGENTIC_LEAN_CONTEXT', true), FILTER_VALIDATE_BOOL),
        'max_history_messages' => (int) env('AGENTIC_CONTEXT_HISTORY', 12),
        'skill_routing_limit' => (int) env('AGENTIC_CONTEXT_SKILL_LIMIT', 4),
        'skills_fallback_limit' => (int) env('AGENTIC_CONTEXT_SKILLS_FALLBACK', 4),
        'knowledge_chunk_limit' => (int) env('AGENTIC_CONTEXT_KNOWLEDGE_LIMIT', 3),
        'memory_entry_limit' => (int) env('AGENTIC_CONTEXT_MEMORY_LIMIT', 8),
        'max_tools' => (int) env('AGENTIC_CONTEXT_MAX_TOOLS', 30),
        'compact_skill_descriptions' => filter_var(env('AGENTIC_CONTEXT_COMPACT_SKILLS', true), FILTER_VALIDATE_BOOL),
        /*
        | Trim what the model sees (history, tool dumps, RAG, memory).
        | Does not hide tools — that is AGENTIC_WIDGET_MAX_TOOLS / deferred tools.
        */
        'compact' => [
            'enabled' => filter_var(env('AGENTIC_COMPACT_INPUT', true), FILTER_VALIDATE_BOOL),
            'history_message_chars' => (int) env('AGENTIC_COMPACT_HISTORY_CHARS', 700),
            'history_recent_chars' => (int) env('AGENTIC_COMPACT_HISTORY_RECENT_CHARS', 1400),
            'history_recent_count' => (int) env('AGENTIC_COMPACT_HISTORY_RECENT', 2),
            'history_total_chars' => (int) env('AGENTIC_COMPACT_HISTORY_TOTAL', 3600),
            'tool_result_chars' => (int) env('AGENTIC_COMPACT_TOOL_RESULT_CHARS', 1800),
            'knowledge_chunk_chars' => (int) env('AGENTIC_COMPACT_KNOWLEDGE_CHARS', 360),
            'memory_entry_chars' => (int) env('AGENTIC_COMPACT_MEMORY_CHARS', 180),
            'tool_description_chars' => (int) env('AGENTIC_COMPACT_TOOL_DESCRIPTION_CHARS', 160),
            'schema_description_chars' => (int) env('AGENTIC_COMPACT_SCHEMA_DESCRIPTION_CHARS', 80),
            'json_string_chars' => (int) env('AGENTIC_COMPACT_JSON_STRING_CHARS', 240),
            'json_list_limit' => (int) env('AGENTIC_COMPACT_JSON_LIST_LIMIT', 12),
            'omit_skill_tool_lists' => filter_var(env('AGENTIC_COMPACT_OMIT_SKILL_TOOLS', true), FILTER_VALIDATE_BOOL),
        ],
    ],

    'skill_routing' => [
        'enabled' => filter_var(env('AGENTIC_SKILL_ROUTING', true), FILTER_VALIDATE_BOOL),
        'limit' => (int) env('AGENTIC_SKILL_ROUTING_LIMIT', 4),
        'fallback_limit' => (int) env('AGENTIC_SKILL_ROUTING_FALLBACK_LIMIT', 4),
        'ai' => [
            'enabled' => filter_var(env('AGENTIC_AI_SKILL_ROUTING', false), FILTER_VALIDATE_BOOL),
            'provider' => env('AGENTIC_AI_SKILL_ROUTING_PROVIDER', env('AGENTIC_AI_PROVIDER')),
            'model' => env('AGENTIC_AI_SKILL_ROUTING_MODEL', env('AGENTIC_AI_MODEL')),
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
        'checker' => env('AGENTIC_PERMISSION_CHECKER', DenyAllPermissionChecker::class),
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
    | Agentic conversation records associate agent/user metadata.
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
        'embedding_provider' => $embeddingProvider = env(
            'AGENTIC_KNOWLEDGE_EMBEDDING_PROVIDER',
            env('AGENTIC_AI_PROVIDER', 'openai'),
        ),
        // OpenAI: text-embedding-3-small (1536). Ollama: nomic-embed-text (768) — run: ollama pull nomic-embed-text
        'embedding_model' => env('AGENTIC_KNOWLEDGE_EMBEDDING_MODEL') ?: match ((string) $embeddingProvider) {
            'ollama' => env('OLLAMA_EMBEDDING_MODEL', 'mxbai-embed-large'),
            default => 'text-embedding-3-small',
        },
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
        'log_queries' => filter_var(env('AGENTIC_RAG_LOG_QUERIES', false), FILTER_VALIDATE_BOOL),
        'log_embeddings' => filter_var(env('AGENTIC_RAG_LOG_EMBEDDINGS', env('AGENTIC_KNOWLEDGE_LOG_EMBEDDINGS', false)), FILTER_VALIDATE_BOOL),
        'log_embedding_preview_dims' => max(1, (int) env('AGENTIC_KNOWLEDGE_LOG_EMBEDDING_PREVIEW', 8)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory
    |--------------------------------------------------------------------------
    |
    | Scoped long-term facts injected into agent context (user, conversation,
    | agent). Opt-in per host app via API or MemoryManager.
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
        'parallel_driver' => env('AGENTIC_WORKFLOW_PARALLEL_DRIVER', 'process'),
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
        'route_name_prefix' => 'agentic.api.',
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
        'http' => HttpToolDriver::class,
        'code' => CodeToolDriver::class,
        'mcp' => McpToolDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom code tools (host app PHP classes)
    |--------------------------------------------------------------------------
    |
    | Place handlers in app/Agentic/Tools/Custom (or paths below). They must
    | implement CodeToolHandler; use DeclarativeCodeToolHandler for auto metadata.
    | Discovered handlers are registered on boot when auto_register is true.
    |
    */
    'code_tools' => [
        'auto_register' => env('AGENTIC_CODE_TOOLS_AUTO_REGISTER', true),
        'paths' => [],
        'namespace' => '',
        'classes' => [],
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
        'inject_prompts' => env('AGENTIC_MCP_INJECT_PROMPTS', true),
        'max_resource_injections' => (int) env('AGENTIC_MCP_MAX_RESOURCE_INJECTIONS', 5),
    ],

    /*
    | Token cost estimates for the admin usage screen (USD per 1M tokens).
    | Tune to your provider invoice — used for planning only, not billing.
    */
    'usage' => [
        'currency' => env('AGENTIC_USAGE_CURRENCY', 'USD'),
        'default' => [
            'input' => (float) env('AGENTIC_USAGE_DEFAULT_INPUT', 2.0),
            'output' => (float) env('AGENTIC_USAGE_DEFAULT_OUTPUT', 10.0),
        ],
        'models' => [
            'claude-sonnet-5' => ['input' => 3.0, 'output' => 15.0],
            'claude-sonnet-4-6' => ['input' => 3.0, 'output' => 15.0],
            'gpt-4.1-mini' => ['input' => 0.4, 'output' => 1.6],
            'gpt-4o' => ['input' => 2.5, 'output' => 10.0],
        ],
    ],
];
