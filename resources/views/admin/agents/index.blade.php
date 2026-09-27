@extends('agentic::admin.layout')

@section('title', 'Agents')

@section('content')
    <h1>Agents</h1>
    <p><a href="{{ route('agentic.admin.agents.create') }}">Create agent</a></p>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($agents as $agent)
                <tr>
                    <td>{{ $agent->name }}</td>
                    <td>{{ $agent->slug }}</td>
                    <td>{{ $agent->status }}</td>
                    <td>
                        <a href="{{ route('agentic.admin.agents.show', $agent->slug) }}">View</a>
                        <a href="{{ route('agentic.admin.agents.edit', $agent->slug) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No agents yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
