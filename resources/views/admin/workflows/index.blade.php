@extends('agentic::admin.layout')

@section('title', __('agentic::admin.workflows.title'))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => __('agentic::admin.workflows.title'),
        'actionUrl' => route('agentic.admin.workflows.create'),
        'actionLabel' => __('agentic::admin.workflows.create'),
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
                    @forelse ($workflows as $workflow)
                        <tr>
                            <td>{{ $workflow->name }}</td>
                            <td><code>{{ $workflow->slug }}</code></td>
                            <td>
                                <span class="badge @if(($workflow->status ?? 'draft') === 'published') badge-published @endif">
                                    {{ __('agentic::admin.status.'.($workflow->status ?? 'draft')) }}
                                </span>
                            </td>
                            <td class="actions">
                                <a class="btn-link" href="{{ route('agentic.admin.workflows.show', $workflow->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                                <a class="btn-link" href="{{ route('agentic.admin.workflows.edit', $workflow->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('agentic::admin.empty.workflows') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
