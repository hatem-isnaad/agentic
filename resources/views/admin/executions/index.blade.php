@extends('agentic::admin.layout')

@section('title', __('agentic::admin.executions.title'))

@section('content')
    <h1>{{ __('agentic::admin.executions.title') }}</h1>
    <table>
        <thead>
            <tr><th>{{ __('agentic::admin.table.id') }}</th><th>{{ __('agentic::admin.table.agent') }}</th><th>{{ __('agentic::admin.table.status') }}</th><th>{{ __('agentic::admin.table.started') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($executions as $execution)
                <tr>
                    <td>{{ $execution->id }}</td>
                    <td>{{ $execution->agent }}</td>
                    <td>{{ $execution->status }}</td>
                    <td>{{ $execution->startedAt }}</td>
                    <td><a href="{{ route('agentic.admin.executions.show', $execution->id) }}">{{ __('agentic::admin.actions.view') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('agentic::admin.empty.executions') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
