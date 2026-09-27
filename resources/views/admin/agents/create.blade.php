@extends('agentic::admin.layout')

@section('title', 'Create agent')

@section('content')
    <h1>Create agent</h1>

    <form method="post" action="{{ route('agentic.admin.agents.store') }}">
        @csrf
        @include('agentic::admin.agents._form')
        <button type="submit">Save</button>
    </form>
@endsection
