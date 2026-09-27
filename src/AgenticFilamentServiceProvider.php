<?php

namespace Agentic;

use Agentic\Filament\AgenticPlugin;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

final class AgenticFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panels = config('agentic.filament.panels', []);

            if ($panels === [] || ! in_array($panel->getId(), $panels, true)) {
                return;
            }

            $panel->plugin(AgenticPlugin::make());
        });
    }
}
