@extends('agentic::admin.layout')

@section('title', 'Edit knowledge source')

@section('content')
    <h1>Edit {{ $source->name }}</h1>
    <form method="post" action="{{ route('agentic.admin.knowledge-sources.update', $source->slug) }}">
        @csrf
        @method('PUT')
        @include('agentic::admin.knowledge-sources._form', ['source' => $source])
        <button type="submit">Update</button>
    </form>
    <form method="post" action="{{ route('agentic.admin.knowledge-sources.destroy', $source->slug) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endsection
