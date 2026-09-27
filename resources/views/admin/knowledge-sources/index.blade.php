@extends('agentic::admin.layout')

@section('title', 'Knowledge sources')

@section('content')
    <h1>Knowledge sources</h1>
    <p><a href="{{ route('agentic.admin.knowledge-sources.create') }}">Create source</a></p>

    <table>
        <thead>
            <tr><th>Name</th><th>Slug</th><th>Driver</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($sources as $source)
                <tr>
                    <td>{{ $source->name }}</td>
                    <td>{{ $source->slug }}</td>
                    <td>{{ $source->driver }}</td>
                    <td>{{ $source->status }}</td>
                    <td>
                        <a href="{{ route('agentic.admin.knowledge-sources.show', $source->slug) }}">View</a>
                        <a href="{{ route('agentic.admin.knowledge-sources.edit', $source->slug) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No knowledge sources yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
