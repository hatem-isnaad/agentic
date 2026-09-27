@extends('agentic::admin.layout')

@section('title', $tool->name)

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => $tool->name,
        'actionUrl' => route('agentic.admin.tools.edit', $tool->slug),
        'actionLabel' => __('agentic::admin.actions.edit'),
    ])

    <div class="card">
        <div class="card-body">
            @include('agentic::admin.partials.detail-rows', ['rows' => [
                [__('agentic::admin.fields.slug'), $tool->slug],
                [__('agentic::admin.fields.driver'), $tool->driver],
                [__('agentic::admin.fields.status'), __('agentic::admin.status.'.($tool->status ?? 'draft'))],
                [__('agentic::admin.fields.description'), $tool->description ?: '—'],
            ]])
        </div>
    </div>
@endsection
