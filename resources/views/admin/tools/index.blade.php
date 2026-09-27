@extends('agentic::admin.layout')

@section('title', __('agentic::admin.tools.title'))

@section('content')
    <h1>{{ __('agentic::admin.tools.title') }}</h1>
    <p><a href="{{ route('agentic.admin.tools.create') }}">{{ __('agentic::admin.tools.create') }}</a></p>

    <table>
        <thead>
            <tr><th>{{ __('agentic::admin.table.name') }}</th><th>{{ __('agentic::admin.table.slug') }}</th><th>{{ __('agentic::admin.table.driver') }}</th><th>{{ __('agentic::admin.table.status') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($tools as $tool)
                <tr>
                    <td>{{ $tool->name }}</td>
                    <td>{{ $tool->slug }}</td>
                    <td>{{ $tool->driver }}</td>
                    <td>{{ __('agentic::admin.status.'.($tool->status ?? 'draft')) }}</td>
                    <td>
                        <a href="{{ route('agentic.admin.tools.show', $tool->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                        <a href="{{ route('agentic.admin.tools.edit', $tool->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('agentic::admin.empty.tools') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
