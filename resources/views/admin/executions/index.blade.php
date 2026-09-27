@extends('agentic::admin.layout')

@section('title', 'Executions')

@section('content')
    <h1>Executions</h1>
    <table>
        <thead>
            <tr><th>ID</th><th>Agent</th><th>Status</th><th>Started</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($executions as $execution)
                <tr>
                    <td>{{ $execution->id }}</td>
                    <td>{{ $execution->agent }}</td>
                    <td>{{ $execution->status }}</td>
                    <td>{{ $execution->startedAt }}</td>
                    <td><a href="{{ route('agentic.admin.executions.show', $execution->id) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No executions yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
