@extends('agentic::admin.layout')

@section('title', 'Create knowledge source')

@section('content')
    <h1>Create knowledge source</h1>
    <form method="post" action="{{ route('agentic.admin.knowledge-sources.store') }}">
        @csrf
        @include('agentic::admin.knowledge-sources._form')
        <button type="submit">Save</button>
    </form>
@endsection
