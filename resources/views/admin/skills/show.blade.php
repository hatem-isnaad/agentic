@extends('agentic::admin.layout')

@section('title', $skill->name)

@section('content')
    <h1>{{ $skill->name }}</h1>
    <p><a href="{{ route('agentic.admin.skills.edit', $skill->slug) }}">{{ __('agentic::admin.actions.edit') }}</a></p>
    <pre>{{ json_encode($skill, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endsection
