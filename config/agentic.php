<?php

return [
    'enabled' => true,

    'tool_drivers' => [
        'http' => Agentic\Tool\Drivers\HttpToolDriver::class,
        'code' => Agentic\Tool\Drivers\CodeToolDriver::class,
    ],
];
