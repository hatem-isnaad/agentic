@extends('agentic::admin.layout')

@section('title', __('agentic::admin.tools.edit_heading', ['name' => $tool->name]))

@section('content')
    <h1>{{ __('agentic::admin.tools.edit_heading', ['name' => $tool->name]) }}</h1>
    <form method="post" action="{{ route('agentic.admin.tools.update', $tool->slug) }}">
        @csrf
        @method('PUT')
        @include('agentic::admin.tools._form', ['tool' => $tool])
        <button type="submit">{{ __('agentic::admin.actions.update') }}</button>
    </form>
    <form method="post" action="{{ route('agentic.admin.tools.destroy', $tool->slug) }}">
        @csrf
        @method('DELETE')
        <button type="submit">{{ __('agentic::admin.actions.delete') }}</button>
    </form>
@endsection
