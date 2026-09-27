@extends('agentic::admin.layout')

@section('title', $agent->name)

@section('content')
    <h1>{{ $agent->name }}</h1>
    <p><a href="{{ route('agentic.admin.agents.edit', $agent->slug) }}">{{ __('agentic::admin.actions.edit') }}</a></p>
    <pre>{{ json_encode($agent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endsection
