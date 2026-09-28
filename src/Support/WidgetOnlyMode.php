<?php

namespace Agentic\Support;

/** @deprecated Use AgenticDeployMode::isWidgetOnly() */
final class WidgetOnlyMode
{
    public static function enabled(): bool
    {
        return AgenticDeployMode::isWidgetOnly();
    }

    public static function applyConfigOverrides(): void
    {
        AgenticDeployMode::applyPresets();
    }
}
