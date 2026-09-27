@extends('agentic::admin.layout')

@section('title', $source->name)

@section('content')
    <h1>{{ $source->name }}</h1>
    <p>
        <a href="{{ route('agentic.admin.knowledge-sources.edit', $source->slug) }}">{{ __('agentic::admin.actions.edit') }}</a>
    </p>
    <form method="post" action="{{ route('agentic.admin.knowledge-sources.index-documents', $source->slug) }}">
        @csrf
        <button type="submit">{{ __('agentic::admin.actions.index_documents') }}</button>
    </form>
    <pre>{{ json_encode($source, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endsection
