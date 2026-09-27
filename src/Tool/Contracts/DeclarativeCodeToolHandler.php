<?php

namespace Agentic\Tool\Contracts;

/**
 * Code tools placed in the host app (e.g. app/Agentic/Tools/Custom).
 * Discovery registers them by handlerName() and exposes metadata to the admin UI.
 */
interface DeclarativeCodeToolHandler extends CodeToolHandler
{
    public static function handlerName(): string;

    public static function toolDescription(): string;

    /**
     * JSON-schema style input for the LLM.
     *
     * @return array<string, mixed>
     */
    public static function inputSchema(): array;
}
