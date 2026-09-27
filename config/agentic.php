<?php

return [
    'enabled' => env('AGENTIC_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Authentication (Sanctum + WebAuthn passkeys)
    |--------------------------------------------------------------------------
    |
    | Registers JSON auth routes under auth.prefix (passkey login/register,
    | session me/token/logout). Host User model must implement PasskeyUser and
    | use HasApiTokens + PasskeyAuthenticatable. Run sanctum + passkeys migrations.
    |
    */
    'auth' => [
        'enabled' => env('AGENTIC_AUTH_ENABLED', true),
        'prefix' => env('AGENTIC_AUTH_PREFIX', 'api/agentic/auth'),
        'route_name_prefix' => 'agentic.auth.',
        'middleware' => [
            'web',
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ],
        'load_migrations' => env('AGENTIC_AUTH_LOAD_MIGRATIONS', false),
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
        /*
        | Host apps and agents pick provider/model per agent; this registry is exposed
        | to admin + widget clients for UI selectors.
        */
        'providers' => [
            'openai' => [
                'label' => 'OpenAI',
                'models' => array_filter(explode(',', (string) env('AGENTIC_OPENAI_MODELS', 'gpt-4.1-mini,gpt-4o'))),
            ],
            'anthropic' => [
                'label' => 'Anthropic',
                'models' => array_filter(explode(',', (string) env('AGENTIC_ANTHROPIC_MODELS', 'claude-sonnet-4-20250514'))),
            ],
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
        'denial_message' => 'Permission denied for tool [:tool].',
        'checker' => env('AGENTIC_PERMISSION_CHECKER', Agentic\Permission\AllowAllPermissionChecker::class),
        /*
        | Optional Laravel Gate abilities (auth|guest|allow|deny).
        */
        'gates' => [
            // 'tool:orders.write' => 'auth',
        ],
        /*
        | Pattern rules when using RuleBasedPermissionChecker.
        | effect: allow | deny | allow_if_auth | allow_if_guest
        */
        'rules' => [
            // ['pattern' => 'tool:db.*', 'effect' => 'allow_if_auth'],
        ],
    ],

    'tool_approval' => [
        'enabled' => env('AGENTIC_TOOL_APPROVAL_ENABLED', true),
        'tool_patterns' => array_filter(explode(',', (string) env('AGENTIC_TOOL_APPROVAL_PATTERNS', '*.write,*.create,*.update,*.delete'))),
        'auto_execute_on_approve' => env('AGENTIC_TOOL_APPROVAL_AUTO_EXECUTE', true),
        'auto_resume_after_execute' => env('AGENTIC_TOOL_APPROVAL_AUTO_RESUME', true),
        'resume_message_template' => env(
            'AGENTIC_TOOL_APPROVAL_RESUME_TEMPLATE',
            'The user approved tool :tool. Result: :result. Continue the conversation naturally for the user.',
        ),
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
        'slug_hint' => env('AGENTIC_ROUTING_SLUG_HINT', true),
        'keyword_map' => [
            // 'support' => ['refund', 'help'],
            // 'sales' => ['pricing', 'quote'],
        ],
        'llm' => [
            'enabled' => env('AGENTIC_AGENT_ROUTING_LLM', false),
            'provider' => env('AGENTIC_AGENT_ROUTING_LLM_PROVIDER'),
            'model' => env('AGENTIC_AGENT_ROUTING_LLM_MODEL'),
            'use_agent_repository' => env('AGENTIC_AGENT_ROUTING_LLM_USE_REPOSITORY', true),
            'catalog' => [
                // ['slug' => 'support', 'label' => 'Support', 'description' => 'Orders & refunds'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Skill routing (runtime — limits tools sent to the LLM)
    |--------------------------------------------------------------------------
    |
    | When enabled, SkillRouter selects a subset of the agent's skills before
    | ContextBuilder composes tools/instructions. Deterministic strategies run
    | first; optional LLM strategy is registered last when llm.enabled=true.
    |
    | fallback: all | core | first | none
    |
    */
    'skill_routing' => [
        'enabled' => env('AGENTIC_SKILL_ROUTING_ENABLED', true),
        'fallback' => env('AGENTIC_SKILL_ROUTING_FALLBACK', 'all'),
        'always_on' => array_filter(explode(',', (string) env('AGENTIC_SKILL_ROUTING_ALWAYS_ON', ''))),
        'use_skill_metadata' => env('AGENTIC_SKILL_ROUTING_USE_SKILL_METADATA', true),
        'keyword_map' => [
            // 'orders' => ['order', 'refund', 'shipping'],
            // 'billing' => ['invoice', 'payment'],
        ],
        'lexical' => [
            'enabled' => env('AGENTIC_SKILL_ROUTING_LEXICAL', true),
            'min_score' => (float) env('AGENTIC_SKILL_ROUTING_LEXICAL_MIN_SCORE', 0.12),
            'max_skills' => (int) env('AGENTIC_SKILL_ROUTING_LEXICAL_MAX_SKILLS', 3),
        ],
        'llm' => [
            'enabled' => env('AGENTIC_SKILL_ROUTING_LLM', false),
            'provider' => env('AGENTIC_SKILL_ROUTING_LLM_PROVIDER'),
            'model' => env('AGENTIC_SKILL_ROUTING_LLM_MODEL'),
            'min_confidence' => (float) env('AGENTIC_SKILL_ROUTING_LLM_MIN_CONFIDENCE', 0.25),
        ],
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
    | Admin (headless API + optional Blade UI)
    |--------------------------------------------------------------------------
    |
    | Use the JSON admin API for a separate SPA dashboard. Blade routes are
    | optional (off by default). Both layers call the same Admin services.
    |
    */
    'admin' => [
        'enabled' => env('AGENTIC_ADMIN_ENABLED', true),

        'api' => [
            'enabled' => env('AGENTIC_ADMIN_API_ENABLED', true),
            'prefix' => env('AGENTIC_ADMIN_API_PREFIX', 'api/agentic/admin'),
            'middleware' => [
                'api',
                Agentic\Http\Middleware\SetAdminLocale::class,
            ],
            'route_name_prefix' => 'agentic.admin.api.',
        ],

        'web' => [
            'enabled' => env('AGENTIC_ADMIN_WEB_ENABLED', false),
            'prefix' => env('AGENTIC_ADMIN_PREFIX', 'agentic/admin'),
            'middleware' => [
                'web',
                Agentic\Http\Middleware\SetAdminLocale::class,
            ],
            'route_name_prefix' => 'agentic.admin.',
        ],

        'default_locale' => env('AGENTIC_ADMIN_LOCALE', 'en'),
        'locales' => ['en', 'ar'],
        'rtl_locales' => ['ar'],
        'locale_session_key' => 'agentic.admin.locale',
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

    /*
    |--------------------------------------------------------------------------
    | Embeddable web chat widget (API-only — host builds UI/themes)
    |--------------------------------------------------------------------------
    */
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
            'mode' => env('AGENTIC_WIDGET_AUTH_MODE', 'both'), // guest | auth | both
            'allow_guest' => env('AGENTIC_WIDGET_ALLOW_GUEST', true),
            'allow_authenticated' => env('AGENTIC_WIDGET_ALLOW_AUTH', true),
        ],

        'conversation' => [
            'allow_multiple' => true,
            'resume_latest' => true,
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
            'rtl_locales' => ['ar'],
            'agent_language' => env('AGENTIC_WIDGET_AGENT_LANGUAGE'),
        ],

        'theme' => [
            'default' => env('AGENTIC_WIDGET_THEME', 'light'),
            'direction' => env('AGENTIC_WIDGET_THEME_DIRECTION', 'auto'), // ltr | rtl | auto
            'custom' => [],
            'sounds' => [],
        ],

        'reply' => [
            'formats' => ['text', 'html', 'table', 'list', 'card', 'code', 'blocks', 'actions'],
        ],

        'broadcast' => [
            'driver' => env('AGENTIC_WIDGET_BROADCAST_DRIVER', 'pusher'),
            'channel_prefix' => env('AGENTIC_WIDGET_BROADCAST_PREFIX', 'agentic-widget'),
            'polling' => [
                'interval_ms' => (int) env('AGENTIC_WIDGET_BROADCAST_POLL_MS', 3000),
            ],
            'socketio' => [
                'url' => env('AGENTIC_WIDGET_SOCKETIO_URL'),
                'token' => env('AGENTIC_WIDGET_SOCKETIO_TOKEN'),
                'timeout' => (int) env('AGENTIC_WIDGET_SOCKETIO_TIMEOUT', 5),
            ],
            'pusher' => [
                'app_id' => env('PUSHER_APP_ID'),
                'key' => env('PUSHER_APP_KEY'),
                'secret' => env('PUSHER_APP_SECRET'),
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
            ],
        ],

        'agents' => [
            // 'support' => [
            //     'welcome_message' => 'How can we help?',
            //     'intake_questions' => ['What is your order id?'],
            //     'agent_language' => 'ar',
            //     'auth_mode' => 'guest',
            // ],
        ],
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
