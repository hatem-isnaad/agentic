@extends('agentic::admin.layout')

@section('title', __('agentic::admin.knowledge.title'))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => __('agentic::admin.knowledge.title'),
        'actionUrl' => route('agentic.admin.knowledge-sources.create'),
        'actionLabel' => __('agentic::admin.knowledge.create'),
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
                    @forelse ($sources as $source)
                        <tr>
                            <td>{{ $source->name }}</td>
                            <td><code>{{ $source->slug }}</code></td>
                            <td>{{ $source->driver }}</td>
                            <td>{{ __('agentic::admin.status.'.($source->status ?? 'draft')) }}</td>
                            <td class="actions">
                                <a class="btn-link" href="{{ route('agentic.admin.knowledge-sources.show', $source->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                                <a class="btn-link" href="{{ route('agentic.admin.knowledge-sources.edit', $source->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('agentic::admin.empty.knowledge') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
