@extends('agentic::admin.layout')

@section('title', __('agentic::admin.agents.title'))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => __('agentic::admin.agents.title'),
        'actionUrl' => route('agentic.admin.agents.create'),
        'actionLabel' => __('agentic::admin.agents.create'),
    ])

    <div class="card">
        <div class="card-body">
            <table class="ag-table">
                <thead>
                    <tr>
                        <th>{{ __('agentic::admin.table.name') }}</th>
                        <th>{{ __('agentic::admin.table.slug') }}</th>
                        <th>{{ __('agentic::admin.table.status') }}</th>
                        <th>{{ __('agentic::admin.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agents as $agent)
                        <tr>
                            <td>{{ $agent->name }}</td>
                            <td><code>{{ $agent->slug }}</code></td>
                            <td>
                                <span class="badge @if(($agent->status?->value ?? $agent->status ?? 'draft') === 'published') badge-published @endif">
                                    {{ __('agentic::admin.status.'.($agent->status?->value ?? $agent->status ?? 'draft')) }}
                                </span>
                            </td>
                            <td class="actions">
                                <a class="btn-link" href="{{ route('agentic.admin.agents.show', $agent->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                                <a class="btn-link" href="{{ route('agentic.admin.agents.edit', $agent->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('agentic::admin.empty.agents') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
