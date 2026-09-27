@extends('agentic::admin.layout')

@section('title', __('agentic::admin.executions.show_title'))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.executions.show_title')])

    <div class="card">
        <div class="card-body">
            @include('agentic::admin.partials.detail-rows', ['rows' => [
                [__('agentic::admin.table.id'), (string) $execution->id],
                [__('agentic::admin.fields.agent'), $execution->agent_slug ?? '—'],
                [__('agentic::admin.fields.status'), __('agentic::admin.status.'.($execution->status?->value ?? $execution->status ?? 'pending'))],
                [__('agentic::admin.fields.started'), optional($execution->started_at)->toDateTimeString() ?? '—'],
            ]])
            <h2 style="font-size:1rem;margin:1.5rem 0 0.75rem;">{{ __('agentic::admin.fields.output') }}</h2>
            <pre class="ag-code">{{ json_encode($execution->output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @if ($execution->steps->isNotEmpty())
                <h2 style="font-size:1rem;margin:1.5rem 0 0.75rem;">{{ __('agentic::admin.fields.trace') }}</h2>
                <pre class="ag-code">{{ json_encode($execution->steps->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </div>
    </div>
@endsection
