@extends('agentic::admin.layout')

@section('title', __('agentic::admin.knowledge.edit_heading', ['name' => $source->name]))

@section('content')
    <h1>{{ __('agentic::admin.knowledge.edit_heading', ['name' => $source->name]) }}</h1>
    <form method="post" action="{{ route('agentic.admin.knowledge-sources.update', $source->slug) }}">
        @csrf
        @method('PUT')
        @include('agentic::admin.knowledge-sources._form', ['source' => $source])
        <button type="submit">{{ __('agentic::admin.actions.update') }}</button>
    </form>
    <form method="post" action="{{ route('agentic.admin.knowledge-sources.destroy', $source->slug) }}">
        @csrf
        @method('DELETE')
        <button type="submit">{{ __('agentic::admin.actions.delete') }}</button>
    </form>
@endsection
