@extends('agentic::admin.layout')

@section('title', __('agentic::admin.knowledge.edit_heading', ['name' => $source->name]))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.knowledge.edit_heading', ['name' => $source->name])])

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('agentic.admin.knowledge-sources.update', $source->slug) }}" class="form-stack">
                @csrf
                @method('PUT')
                @include('agentic::admin.knowledge-sources._form', ['source' => $source])
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('agentic::admin.actions.update') }}</button>
                </div>
            </form>
            <form method="post" action="{{ route('agentic.admin.knowledge-sources.destroy', $source->slug) }}" class="form-actions" onsubmit="return confirm(@json(__('agentic::admin.actions.confirm_delete')));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('agentic::admin.actions.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
