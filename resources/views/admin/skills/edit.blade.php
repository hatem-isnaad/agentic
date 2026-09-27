@extends('agentic::admin.layout')

@section('title', __('agentic::admin.skills.edit_heading', ['name' => $skill->name]))

@section('content')
    <h1>{{ __('agentic::admin.skills.edit_heading', ['name' => $skill->name]) }}</h1>
    <form method="post" action="{{ route('agentic.admin.skills.update', $skill->slug) }}">
        @csrf
        @method('PUT')
        @include('agentic::admin.skills._form', ['skill' => $skill])
        <button type="submit">{{ __('agentic::admin.actions.update') }}</button>
    </form>
    <form method="post" action="{{ route('agentic.admin.skills.destroy', $skill->slug) }}">
        @csrf
        @method('DELETE')
        <button type="submit">{{ __('agentic::admin.actions.delete') }}</button>
    </form>
@endsection
