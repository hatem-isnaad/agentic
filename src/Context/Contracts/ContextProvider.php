<?php

namespace Agentic\Context\Contracts;

use Agentic\Context\RuntimeContext;

/**
 * Host applications register providers to contribute runtime context values.
 */
interface ContextProvider
{
    /**
     * @return array<string, mixed>
     */
    public function provide(RuntimeContext $context): array;
}
