<?php

namespace Agentic\Context\Providers;

use Agentic\Context\Contracts\ContextProvider;
use Agentic\Context\RuntimeContext;
use Closure;

final class CallbackContextProvider implements ContextProvider
{
    public function __construct(
        private Closure $callback,
    ) {}

    public function provide(RuntimeContext $context): array
    {
        $values = ($this->callback)($context);

        return is_array($values) ? $values : [];
    }
}
