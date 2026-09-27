@extends('agentic::admin.layout')

@section('title', __('agentic::admin.conversations.show_title'))

@section('content')
    <h1>{{ __('agentic::admin.conversations.show_title') }}</h1>
    <pre>{{ json_encode($conversation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endsection
