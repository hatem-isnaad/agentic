@extends('agentic::admin.layout')

@section('title', __('agentic::admin.workflow_runs.title'))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.workflow_runs.title')])

    <div class="card">
        <div class="card-body">
            <table class="ag-table">
                <thead>
                    <tr>
                        <th>{{ __('agentic::admin.table.uuid') }}</th>
                        <th>{{ __('agentic::admin.table.workflow') }}</th>
                        <th>{{ __('agentic::admin.table.status') }}</th>
                        <th>{{ __('agentic::admin.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td><code>{{ \Illuminate\Support\Str::limit($run->uuid, 12, '') }}</code></td>
                            <td>{{ $run->workflowSlug }}</td>
                            <td>{{ __('agentic::admin.status.'.$run->status) }}</td>
                            <td>
                                <a class="btn-link" href="{{ route('agentic.admin.workflow-runs.show', $run->uuid) }}">{{ __('agentic::admin.actions.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('agentic::admin.empty.workflow_runs') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
