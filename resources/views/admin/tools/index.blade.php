@extends('agentic::admin.layout')

@section('title', 'Tools')

@section('content')
    <h1>Tools</h1>
    <p><a href="{{ route('agentic.admin.tools.create') }}">Create tool</a></p>

    <table>
        <thead>
            <tr><th>Name</th><th>Slug</th><th>Driver</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($tools as $tool)
                <tr>
                    <td>{{ $tool->name }}</td>
                    <td>{{ $tool->slug }}</td>
                    <td>{{ $tool->driver }}</td>
                    <td>{{ $tool->status }}</td>
                    <td>
                        <a href="{{ route('agentic.admin.tools.show', $tool->slug) }}">View</a>
                        <a href="{{ route('agentic.admin.tools.edit', $tool->slug) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No tools yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
