@extends('agentic::admin.layout')

@section('title', 'Create tool')

@section('content')
    <h1>Create tool</h1>
    <form method="post" action="{{ route('agentic.admin.tools.store') }}">
        @csrf
        @include('agentic::admin.tools._form')
        <button type="submit">Save</button>
    </form>
@endsection
