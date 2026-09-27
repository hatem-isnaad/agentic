@extends('agentic::admin.layout')

@section('title', $tool->name)

@section('content')
    <h1>{{ $tool->name }}</h1>
    <p><a href="{{ route('agentic.admin.tools.edit', $tool->slug) }}">Edit</a></p>
    <pre>{{ json_encode($tool, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endsection
