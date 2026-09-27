@extends('agentic::admin.layout')

@section('title', $agent->name)

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => $agent->name,
        'actionUrl' => route('agentic.admin.agents.edit', $agent->slug),
        'actionLabel' => __('agentic::admin.actions.edit'),
    ])

    <div class="card">
        <div class="card-body">
            @include('agentic::admin.partials.detail-rows', ['rows' => [
                [__('agentic::admin.fields.slug'), $agent->slug],
                [__('agentic::admin.fields.status'), __('agentic::admin.status.'.($agent->status?->value ?? $agent->status ?? 'draft'))],
                [__('agentic::admin.fields.description'), $agent->description ?: '—'],
                [__('agentic::admin.fields.provider'), $agent->provider ?: '—'],
                [__('agentic::admin.fields.model'), $agent->model ?: '—'],
            ]])
        </div>
    </div>
@endsection
