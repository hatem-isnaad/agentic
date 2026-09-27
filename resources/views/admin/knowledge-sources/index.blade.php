@extends('agentic::admin.layout')

@section('title', __('agentic::admin.knowledge.title'))

@section('content')
    <h1>{{ __('agentic::admin.knowledge.title') }}</h1>
    <p><a href="{{ route('agentic.admin.knowledge-sources.create') }}">{{ __('agentic::admin.knowledge.create') }}</a></p>

    <table>
        <thead>
            <tr><th>{{ __('agentic::admin.table.name') }}</th><th>{{ __('agentic::admin.table.slug') }}</th><th>{{ __('agentic::admin.table.driver') }}</th><th>{{ __('agentic::admin.table.status') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($sources as $source)
                <tr>
                    <td>{{ $source->name }}</td>
                    <td>{{ $source->slug }}</td>
                    <td>{{ $source->driver }}</td>
                    <td>{{ __('agentic::admin.status.'.($source->status ?? 'draft')) }}</td>
                    <td>
                        <a href="{{ route('agentic.admin.knowledge-sources.show', $source->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                        <a href="{{ route('agentic.admin.knowledge-sources.edit', $source->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('agentic::admin.empty.knowledge') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
