@extends('agentic::admin.layout')

@section('title', __('agentic::admin.tools.edit_heading', ['name' => $tool->name]))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.tools.edit_heading', ['name' => $tool->name])])

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('agentic.admin.tools.update', $tool->slug) }}" class="form-stack">
                @csrf
                @method('PUT')
                @include('agentic::admin.tools._form', ['tool' => $tool])
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('agentic::admin.actions.update') }}</button>
                </div>
            </form>
            <form method="post" action="{{ route('agentic.admin.tools.destroy', $tool->slug) }}" class="form-actions" onsubmit="return confirm(@json(__('agentic::admin.actions.confirm_delete')));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('agentic::admin.actions.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
