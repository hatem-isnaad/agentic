@extends('agentic::admin.layout')

@section('title', 'Skills')

@section('content')
    <h1>Skills</h1>
    <p><a href="{{ route('agentic.admin.skills.create') }}">Create skill</a></p>

    <table>
        <thead>
            <tr><th>Name</th><th>Slug</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($skills as $skill)
                <tr>
                    <td>{{ $skill->name }}</td>
                    <td>{{ $skill->slug }}</td>
                    <td>{{ $skill->status }}</td>
                    <td>
                        <a href="{{ route('agentic.admin.skills.show', $skill->slug) }}">View</a>
                        <a href="{{ route('agentic.admin.skills.edit', $skill->slug) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No skills yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
