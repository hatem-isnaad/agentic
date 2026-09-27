@extends('agentic::admin.layout')

@section('title', 'Edit agent')

@section('content')
    <h1>Edit {{ $agent->name }}</h1>

    <form method="post" action="{{ route('agentic.admin.agents.update', $agent->slug) }}">
        @csrf
        @method('PUT')
        @include('agentic::admin.agents._form', ['agent' => $agent])
        <button type="submit">Update</button>
    </form>

    <form method="post" action="{{ route('agentic.admin.agents.destroy', $agent->slug) }}" onsubmit="return confirm('Delete this agent?');">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endsection
