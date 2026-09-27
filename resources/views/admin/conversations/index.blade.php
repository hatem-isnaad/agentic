@extends('agentic::admin.layout')

@section('title', __('agentic::admin.conversations.title'))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.conversations.title')])

    <div class="card">
        <div class="card-body">
            <table class="ag-table">
                <thead>
                    <tr>
                        <th>{{ __('agentic::admin.table.id') }}</th>
                        <th>{{ __('agentic::admin.table.agent') }}</th>
                        <th>{{ __('agentic::admin.table.user') }}</th>
                        <th>{{ __('agentic::admin.table.updated') }}</th>
                        <th>{{ __('agentic::admin.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($conversations as $conversation)
                        <tr>
                            <td>{{ $conversation->id }}</td>
                            <td>{{ $conversation->agent_slug ?? '—' }}</td>
                            <td>{{ $conversation->user_id ?? $conversation->guest_id ?? '—' }}</td>
                            <td>{{ optional($conversation->updated_at)->toDateTimeString() ?? '—' }}</td>
                            <td>
                                <a class="btn-link" href="{{ route('agentic.admin.conversations.show', $conversation->id) }}">{{ __('agentic::admin.actions.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('agentic::admin.empty.conversations') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
