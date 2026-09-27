@extends('agentic::admin.layout')

@section('title', 'Execution '.$execution->id)

@section('content')
    <h1>Execution</h1>
    <pre>{{ json_encode($execution, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
@endsection
