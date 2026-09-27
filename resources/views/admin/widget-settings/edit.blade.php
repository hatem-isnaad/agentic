@extends('agentic::admin.layout')

@section('title', __('agentic::admin.widget_settings.edit_heading', ['agent' => $agentSlug]))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => __('agentic::admin.widget_settings.edit_heading', ['agent' => $agentSlug]),
        'actionUrl' => route('agentic.admin.widget-settings.index'),
        'actionLabel' => __('agentic::admin.actions.back'),
    ])

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('agentic.admin.widget-settings.update', $agentSlug) }}" class="form-stack">
                @csrf
                @method('PUT')
                <label for="settings_json">{{ __('agentic::admin.fields.settings') }}</label>
                <p class="lead" style="margin:0.25rem 0 0.5rem;font-size:0.85rem;color:var(--ag-muted);">{{ __('agentic::admin.widget_settings.json_hint') }}</p>
                <textarea name="settings_json" id="settings_json" class="code" required>{{ old('settings_json', $settingsJson) }}</textarea>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('agentic::admin.actions.update') }}</button>
                </div>
            </form>
            <form method="post" action="{{ route('agentic.admin.widget-settings.destroy', $agentSlug) }}" class="form-actions" onsubmit="return confirm(@json(__('agentic::admin.actions.confirm_delete')));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('agentic::admin.actions.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
