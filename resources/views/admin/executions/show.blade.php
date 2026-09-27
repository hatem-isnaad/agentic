@extends('agentic::admin.layout')

@section('title', __('agentic::admin.executions.show_title'))

@section('content')
    <h1>{{ __('agentic::admin.executions.show_title') }}</h1>
    <pre>{{ json_encode($execution, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endsection
