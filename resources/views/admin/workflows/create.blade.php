@extends('agentic::admin.layout')

@section('title', __('agentic::admin.workflows.create_heading'))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.workflows.create_heading')])

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('agentic.admin.workflows.store') }}" class="form-stack">
                @csrf
                @include('agentic::admin.workflows._form', ['workflow' => null])
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('agentic::admin.actions.save') }}</button>
                    <a href="{{ route('agentic.admin.workflows.index') }}" class="btn btn-ghost">{{ __('agentic::admin.actions.back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
