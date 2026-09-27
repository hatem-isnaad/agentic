@extends('agentic::admin.layout')

@section('title', __('agentic::admin.skills.title'))

@section('content')
    <h1>{{ __('agentic::admin.skills.title') }}</h1>
    <p><a href="{{ route('agentic.admin.skills.create') }}">{{ __('agentic::admin.skills.create') }}</a></p>

    <table>
        <thead>
            <tr><th>{{ __('agentic::admin.table.name') }}</th><th>{{ __('agentic::admin.table.slug') }}</th><th>{{ __('agentic::admin.table.status') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($skills as $skill)
                <tr>
                    <td>{{ $skill->name }}</td>
                    <td>{{ $skill->slug }}</td>
                    <td>{{ __('agentic::admin.status.'.($skill->status ?? 'draft')) }}</td>
                    <td>
                        <a href="{{ route('agentic.admin.skills.show', $skill->slug) }}">{{ __('agentic::admin.actions.view') }}</a>
                        <a href="{{ route('agentic.admin.skills.edit', $skill->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('agentic::admin.empty.skills') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
