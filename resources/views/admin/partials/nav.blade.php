<aside class="ag-sidebar">
    <div class="ag-brand">{{ __('agentic::admin.app_title') }}</div>
    <nav class="ag-nav" aria-label="{{ __('agentic::admin.app_title') }}">
        @php
            $links = [
                ['agentic.admin.dashboard', __('agentic::admin.nav.dashboard'), []],
                ['agentic.admin.agents.index', __('agentic::admin.nav.agents'), []],
                ['agentic.admin.skills.index', __('agentic::admin.nav.skills'), []],
                ['agentic.admin.tools.index', __('agentic::admin.nav.tools'), []],
                ['agentic.admin.knowledge-sources.index', __('agentic::admin.nav.knowledge'), []],
                ['agentic.admin.workflows.index', __('agentic::admin.nav.workflows'), []],
                ['agentic.admin.workflow-runs.index', __('agentic::admin.nav.workflow_runs'), []],
                ['agentic.admin.executions.index', __('agentic::admin.nav.executions'), []],
                ['agentic.admin.conversations.index', __('agentic::admin.nav.conversations'), []],
                ['agentic.admin.widget-settings.index', __('agentic::admin.nav.widget_settings'), []],
            ];
        @endphp
        @foreach ($links as [$route, $label, $params])
            @if (Route::has($route))
                @php
                    $active = $route === 'agentic.admin.dashboard'
                        ? request()->routeIs('agentic.admin.dashboard')
                        : request()->routeIs(preg_replace('/\.[^.]+$/', '.*', $route));
                @endphp
                <a href="{{ route($route, $params) }}" @class(['is-active' => $active])>{{ $label }}</a>
            @endif
        @endforeach
        @if (Route::has('agentic.widget.web.chat'))
            <a href="{{ route('agentic.widget.web.chat', ['agent' => 'support']) }}" @class(['is-active' => request()->routeIs('agentic.widget.web.*')])>{{ __('agentic::admin.nav.widget') }}</a>
        @endif
    </nav>
</aside>
