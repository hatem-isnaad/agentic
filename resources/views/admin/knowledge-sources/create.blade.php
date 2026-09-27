@extends('agentic::admin.layout')

@section('title', __('agentic::admin.knowledge.create_heading'))

@section('content')
    <h1>{{ __('agentic::admin.knowledge.create_heading') }}</h1>
    <form method="post" action="{{ route('agentic.admin.knowledge-sources.store') }}">
        @csrf
        @include('agentic::admin.knowledge-sources._form')
        <button type="submit">{{ __('agentic::admin.actions.save') }}</button>
    </form>
@endsection
