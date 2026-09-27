@extends('agentic::admin.layout')

@section('title', 'Create skill')

@section('content')
    <h1>Create skill</h1>
    <form method="post" action="{{ route('agentic.admin.skills.store') }}">
        @csrf
        @include('agentic::admin.skills._form')
        <button type="submit">Save</button>
    </form>
@endsection
