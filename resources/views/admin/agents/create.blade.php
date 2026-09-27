@extends('agentic::admin.layout')

@section('title', __('agentic::admin.agents.create_heading'))

@section('content')
    <h1>{{ __('agentic::admin.agents.create_heading') }}</h1>

    <form method="post" action="{{ route('agentic.admin.agents.store') }}">
        @csrf
        @include('agentic::admin.agents._form')
        <button type="submit">{{ __('agentic::admin.actions.save') }}</button>
    </form>
@endsection
