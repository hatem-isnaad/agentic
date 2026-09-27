@extends('agentic::admin.layout')

@section('title', $skill->name)

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => $skill->name,
        'actionUrl' => route('agentic.admin.skills.edit', $skill->slug),
        'actionLabel' => __('agentic::admin.actions.edit'),
    ])

    <div class="card">
        <div class="card-body">
            @include('agentic::admin.partials.detail-rows', ['rows' => [
                [__('agentic::admin.fields.slug'), $skill->slug],
                [__('agentic::admin.fields.status'), __('agentic::admin.status.'.($skill->status ?? 'draft'))],
                [__('agentic::admin.fields.description'), $skill->description ?: '—'],
            ]])
        </div>
    </div>
@endsection
