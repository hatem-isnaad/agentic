@extends('agentic::admin.layout')

@section('title', __('agentic::admin.agents.edit_heading', ['name' => $agent->name]))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.agents.edit_heading', ['name' => $agent->name])])

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('agentic.admin.agents.update', $agent->slug) }}" class="form-stack">
                @csrf
                @method('PUT')
                @include('agentic::admin.agents._form', ['agent' => $agent])
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('agentic::admin.actions.update') }}</button>
                </div>
            </form>
            <form method="post" action="{{ route('agentic.admin.agents.destroy', $agent->slug) }}" class="form-actions" onsubmit="return confirm(@json(__('agentic::admin.actions.confirm_delete_agent')));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('agentic::admin.actions.delete') }}</button>
            </form>
        </div>
    </div>
@endsection
