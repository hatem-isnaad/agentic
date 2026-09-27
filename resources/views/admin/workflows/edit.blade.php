@extends('agentic::admin.layout')

@section('title', __('agentic::admin.workflows.edit_heading', ['name' => $workflow->name]))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.workflows.edit_heading', ['name' => $workflow->name])])

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('agentic.admin.workflows.update', $workflow->slug) }}" class="form-stack">
                @csrf
                @method('PUT')
                @include('agentic::admin.workflows._form', ['workflow' => $workflow])
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('agentic::admin.actions.update') }}</button>
                </div>
            </form>
            <form method="post" action="{{ route('agentic.admin.workflows.destroy', $workflow->slug) }}" class="form-actions" onsubmit="return confirm(@json(__('agentic::admin.actions.confirm_delete_workflow')));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('agentic::admin.actions.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
