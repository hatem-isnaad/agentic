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
