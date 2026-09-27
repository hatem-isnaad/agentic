@extends('agentic::admin.layout')

@section('title', 'Conversation '.$conversation->id)

@section('content')
    <h1>Conversation</h1>
    <pre>{{ json_encode($conversation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endsection
