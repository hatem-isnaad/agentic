<?php

return [
    'enabled' => env('AGENTIC_ENABLED', true),

    'ai' => [
        'provider' => env('AGENTIC_AI_PROVIDER'),
        'model' => env('AGENTIC_AI_MODEL'),
    ],

    'permissions' => [
        'default' => env('AGENTIC_PERMISSION_DEFAULT', 'deny'),
        'checker' => Agentic\Permission\DenyAllPermissionChecker::class,
        'denial_message' => 'Permission denied for tool [:tool].',
    ],

    'execution' => [
        'driver' => env('AGENTIC_EXECUTION_DRIVER', 'eloquent'),
    ],

    'conversation' => [
        'driver' => env('AGENTIC_CONVERSATION_DRIVER', 'eloquent'),
    ],

    'routing' => [
        'fallback_agent' => env('AGENTIC_FALLBACK_AGENT'),
    ],

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

    'filament' => [
        'panels' => array_filter(explode(',', (string) env('AGENTIC_FILAMENT_PANELS', 'admin'))),
    ],

    'api' => [
        'enabled' => env('AGENTIC_API_ENABLED', false),
        'prefix' => env('AGENTIC_API_PREFIX', 'api/agentic'),
        'middleware' => ['api'],
    ],

    'tool_drivers' => [
        'http' => Agentic\Tool\Drivers\HttpToolDriver::class,
        'code' => Agentic\Tool\Drivers\CodeToolDriver::class,
        'mcp' => Agentic\Tool\Drivers\McpToolDriver::class,
    ],
];
