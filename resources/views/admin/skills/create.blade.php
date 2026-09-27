@extends('agentic::admin.layout')

@section('title', __('agentic::admin.skills.create_heading'))

@section('content')
    <h1>{{ __('agentic::admin.skills.create_heading') }}</h1>
    <form method="post" action="{{ route('agentic.admin.skills.store') }}">
        @csrf
        @include('agentic::admin.skills._form')
        <button type="submit">{{ __('agentic::admin.actions.save') }}</button>
    </form>
@endsection
