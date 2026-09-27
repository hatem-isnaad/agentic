@extends('agentic::admin.layout')

@section('title', __('agentic::admin.executions.title'))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.executions.title')])

    <div class="card">
        <div class="card-body">
            <table class="ag-table">
                <thead>
                    <tr>
                        <th>{{ __('agentic::admin.table.id') }}</th>
                        <th>{{ __('agentic::admin.table.agent') }}</th>
                        <th>{{ __('agentic::admin.table.status') }}</th>
                        <th>{{ __('agentic::admin.table.started') }}</th>
                        <th>{{ __('agentic::admin.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($executions as $execution)
                        <tr>
                            <td>{{ $execution->id }}</td>
                            <td>{{ $execution->agent_slug ?? '—' }}</td>
                            <td>{{ __('agentic::admin.status.'.($execution->status?->value ?? $execution->status ?? 'pending')) }}</td>
                            <td>{{ optional($execution->started_at)->toDateTimeString() ?? '—' }}</td>
                            <td>
                                <a class="btn-link" href="{{ route('agentic.admin.executions.show', $execution->id) }}">{{ __('agentic::admin.actions.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('agentic::admin.empty.executions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
