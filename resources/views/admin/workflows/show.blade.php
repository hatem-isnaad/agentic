@extends('agentic::admin.layout')

@section('title', __('agentic::admin.workflows.show_heading'))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => $workflow->name,
        'actionUrl' => route('agentic.admin.workflows.edit', $workflow->slug),
        'actionLabel' => __('agentic::admin.actions.edit'),
    ])

    <div class="card">
        <div class="card-body">
            @include('agentic::admin.partials.detail-rows', ['rows' => [
                [__('agentic::admin.fields.slug'), $workflow->slug],
                [__('agentic::admin.fields.status'), __('agentic::admin.status.'.($workflow->status ?? 'draft'))],
                [__('agentic::admin.fields.description'), $workflow->description ?: '—'],
            ]])
            <h2 style="font-size:1rem;margin:1.5rem 0 0.75rem;">{{ __('agentic::admin.fields.steps') }}</h2>
            <pre class="ag-code" dir="ltr">{{ json_encode($workflow->steps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
@endsection
