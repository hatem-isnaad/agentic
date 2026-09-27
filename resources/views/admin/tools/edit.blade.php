@extends('agentic::admin.layout')

@section('title', 'Edit tool')

@section('content')
    <h1>Edit {{ $tool->name }}</h1>
    <form method="post" action="{{ route('agentic.admin.tools.update', $tool->slug) }}">
        @csrf
        @method('PUT')
        @include('agentic::admin.tools._form', ['tool' => $tool])
        <button type="submit">Update</button>
    </form>
    <form method="post" action="{{ route('agentic.admin.tools.destroy', $tool->slug) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endsection
