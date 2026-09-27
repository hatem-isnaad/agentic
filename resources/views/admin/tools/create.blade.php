@extends('agentic::admin.layout')

@section('title', __('agentic::admin.tools.create_heading'))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.tools.create_heading')])

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('agentic.admin.tools.store') }}" class="form-stack">
                @csrf
                @include('agentic::admin.tools._form')
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('agentic::admin.actions.save') }}</button>
                    <a href="{{ route('agentic.admin.tools.index') }}" class="btn btn-ghost">{{ __('agentic::admin.actions.back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
