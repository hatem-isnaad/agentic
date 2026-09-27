@extends('agentic::admin.layout')

@section('title', __('agentic::admin.agents.title'))

@section('content')
    <h1>{{ __('agentic::admin.agents.title') }}</h1>
    <p><a href="{{ route('agentic.admin.agents.create') }}">{{ __('agentic::admin.agents.create') }}</a></p>

    <table>
        <thead>
            <tr>
                <th>{{ __('agentic::admin.table.name') }}</th>
                <th>{{ __('agentic::admin.table.slug') }}</th>
                <th>{{ __('agentic::admin.table.status') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($agents as $agent)
                <tr>
                    <td>{{ $agent->name }}</td>
                    <td>{{ $agent->slug }}</td>
                    <td>{{ __('agentic::admin.status.'.($agent->status ?? 'draft')) }}</td>
                    <td>
                        <a href="{{ route('agentic.admin.agents.show', $agent->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                        <a href="{{ route('agentic.admin.agents.edit', $agent->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('agentic::admin.empty.agents') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
