@extends('agentic::admin.layout')

@section('title', __('agentic::admin.skills.title'))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => __('agentic::admin.skills.title'),
        'actionUrl' => route('agentic.admin.skills.create'),
        'actionLabel' => __('agentic::admin.skills.create'),
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
                    @forelse ($skills as $skill)
                        <tr>
                            <td>{{ $skill->name }}</td>
                            <td><code>{{ $skill->slug }}</code></td>
                            <td>{{ __('agentic::admin.status.'.($skill->status ?? 'draft')) }}</td>
                            <td class="actions">
                                <a class="btn-link" href="{{ route('agentic.admin.skills.show', $skill->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                                <a class="btn-link" href="{{ route('agentic.admin.skills.edit', $skill->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('agentic::admin.empty.skills') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
