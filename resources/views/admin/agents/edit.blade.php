@extends('agentic::admin.layout')

@section('title', __('agentic::admin.agents.edit_heading', ['name' => $agent->name]))

@section('content')
    <h1>{{ __('agentic::admin.agents.edit_heading', ['name' => $agent->name]) }}</h1>

    <form method="post" action="{{ route('agentic.admin.agents.update', $agent->slug) }}">
        @csrf
        @method('PUT')
        @include('agentic::admin.agents._form', ['agent' => $agent])
        <button type="submit">{{ __('agentic::admin.actions.update') }}</button>
    </form>

    <form method="post" action="{{ route('agentic.admin.agents.destroy', $agent->slug) }}" onsubmit="return confirm(@json(__('agentic::admin.actions.confirm_delete_agent')));">
        @csrf
        @method('DELETE')
        <button type="submit">{{ __('agentic::admin.actions.delete') }}</button>
    </form>
@endsection
