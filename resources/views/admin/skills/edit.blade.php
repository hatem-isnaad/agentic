@extends('agentic::admin.layout')

@section('title', 'Edit skill')

@section('content')
    <h1>Edit {{ $skill->name }}</h1>
    <form method="post" action="{{ route('agentic.admin.skills.update', $skill->slug) }}">
        @csrf
        @method('PUT')
        @include('agentic::admin.skills._form', ['skill' => $skill])
        <button type="submit">Update</button>
    </form>
    <form method="post" action="{{ route('agentic.admin.skills.destroy', $skill->slug) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endsection
