@extends('agentic::admin.layout')

@section('title', __('agentic::admin.conversations.title'))

@section('content')
    <h1>{{ __('agentic::admin.conversations.title') }}</h1>
    <table>
        <thead>
            <tr><th>{{ __('agentic::admin.table.id') }}</th><th>{{ __('agentic::admin.table.agent') }}</th><th>{{ __('agentic::admin.table.user') }}</th><th>{{ __('agentic::admin.table.updated') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($conversations as $conversation)
                <tr>
                    <td>{{ $conversation->id }}</td>
                    <td>{{ $conversation->agent }}</td>
                    <td>{{ $conversation->userId }}</td>
                    <td>{{ $conversation->updatedAt }}</td>
                    <td><a href="{{ route('agentic.admin.conversations.show', $conversation->id) }}">{{ __('agentic::admin.actions.view') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('agentic::admin.empty.conversations') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
