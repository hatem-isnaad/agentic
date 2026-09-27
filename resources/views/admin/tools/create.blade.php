@extends('agentic::admin.layout')

@section('title', __('agentic::admin.tools.create_heading'))

@section('content')
    <h1>{{ __('agentic::admin.tools.create_heading') }}</h1>
    <form method="post" action="{{ route('agentic.admin.tools.store') }}">
        @csrf
        @include('agentic::admin.tools._form')
        <button type="submit">{{ __('agentic::admin.actions.save') }}</button>
    </form>
@endsection
