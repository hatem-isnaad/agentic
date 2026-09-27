<?php

namespace Agentic\Context\Providers;

use Agentic\Context\Contracts\ContextProvider;
use Agentic\Context\RuntimeContext;

/**
 * Static key/value context contributions.
 */
final class ArrayContextProvider implements ContextProvider
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        private array $values = [],
    ) {}

    public function provide(RuntimeContext $context): array
    {
        return $this->values;
    }
}
