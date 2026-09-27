@extends('agentic::admin.layout')

@section('title', __('agentic::admin.workflow_runs.show_heading'))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.workflow_runs.show_heading')])

    <div class="card">
        <div class="card-body">
            @include('agentic::admin.partials.detail-rows', ['rows' => [
                [__('agentic::admin.table.uuid'), $run->uuid],
                [__('agentic::admin.fields.workflow'), $run->workflowSlug],
                [__('agentic::admin.fields.status'), __('agentic::admin.status.'.$run->status)],
                [__('agentic::admin.fields.step_pointer'), (string) $run->stepPointer],
            ]])
            @if ($run->error)
                <p class="alert alert-error" style="margin-top:1rem;">{{ $run->error }}</p>
            @endif
            <h2 style="font-size:1rem;margin:1.5rem 0 0.75rem;">{{ __('agentic::admin.fields.variables') }}</h2>
            <pre class="ag-code">{{ json_encode($run->variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            <h2 style="font-size:1rem;margin:1.5rem 0 0.75rem;">{{ __('agentic::admin.fields.trace') }}</h2>
            <pre class="ag-code">{{ json_encode($run->trace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
@endsection
