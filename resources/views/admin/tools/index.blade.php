@extends('agentic::admin.layout')

@section('title', __('agentic::admin.tools.title'))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => __('agentic::admin.tools.title'),
        'actionUrl' => route('agentic.admin.tools.create'),
        'actionLabel' => __('agentic::admin.tools.create'),
    ])

    <div class="card">
        <div class="card-body">
            <table class="ag-table">
                <thead>
                    <tr>
                        <th>{{ __('agentic::admin.table.name') }}</th>
                        <th>{{ __('agentic::admin.table.slug') }}</th>
                        <th>{{ __('agentic::admin.table.driver') }}</th>
                        <th>{{ __('agentic::admin.table.status') }}</th>
                        <th>{{ __('agentic::admin.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tools as $tool)
                        <tr>
                            <td>{{ $tool->name }}</td>
                            <td><code>{{ $tool->slug }}</code></td>
                            <td>{{ $tool->driver }}</td>
                            <td>{{ __('agentic::admin.status.'.($tool->status ?? 'draft')) }}</td>
                            <td class="actions">
                                <a class="btn-link" href="{{ route('agentic.admin.tools.show', $tool->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                                <a class="btn-link" href="{{ route('agentic.admin.tools.edit', $tool->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('agentic::admin.empty.tools') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
