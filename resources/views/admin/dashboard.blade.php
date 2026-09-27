@extends('agentic::admin.layout')

@section('title', 'Agentic Dashboard')

@section('content')
    <h1>Agentic Admin (placeholder)</h1>
    <p>Replace these views with your host application dashboard UI.</p>

    <ul>
        <li>Agents: {{ $stats->agents }}</li>
        <li>Skills: {{ $stats->skills }}</li>
        <li>Tools: {{ $stats->tools }}</li>
        <li>Knowledge sources: {{ $stats->knowledgeSources }}</li>
        <li>Executions: {{ $stats->executions }}</li>
        <li>Conversations: {{ $stats->conversations }}</li>
    </ul>
@endsection
