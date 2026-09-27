@extends('agentic::admin.layout')

@section('title', __('agentic::admin.widget_settings.title'))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => __('agentic::admin.widget_settings.title'),
        'lead' => __('agentic::admin.widget_settings.intro'),
    ])

    <div class="card">
        <div class="card-body">
            <table class="ag-table">
                <thead>
                    <tr>
                        <th>{{ __('agentic::admin.table.agent') }}</th>
                        <th>{{ __('agentic::admin.table.status') }}</th>
                        <th>{{ __('agentic::admin.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agents as $agent)
                        <tr>
                            <td>{{ $agent->name }} <code>{{ $agent->slug }}</code></td>
                            <td>
                                @if ($overrides->has($agent->slug))
                                    <span class="badge badge-published">{{ __('agentic::admin.widget_settings.configured') }}</span>
                                @else
                                    <span class="badge">{{ __('agentic::admin.widget_settings.default') }}</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn-link" href="{{ route('agentic.admin.widget-settings.edit', $agent->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">{{ __('agentic::admin.empty.agents') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
