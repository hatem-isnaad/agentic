@extends('agentic::admin.layout')

@section('title', 'Conversations')

@section('content')
    <h1>Conversations</h1>
    <table>
        <thead>
            <tr><th>ID</th><th>Agent</th><th>User</th><th>Updated</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($conversations as $conversation)
                <tr>
                    <td>{{ $conversation->id }}</td>
                    <td>{{ $conversation->agent }}</td>
                    <td>{{ $conversation->userId }}</td>
                    <td>{{ $conversation->updatedAt }}</td>
                    <td><a href="{{ route('agentic.admin.conversations.show', $conversation->id) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No conversations yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
