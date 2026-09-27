@extends('agentic::admin.layout')

@section('title', $source->name)

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => $source->name,
        'actionUrl' => route('agentic.admin.knowledge-sources.edit', $source->slug),
        'actionLabel' => __('agentic::admin.actions.edit'),
    ])

    <div class="card">
        <div class="card-body">
            @include('agentic::admin.partials.detail-rows', ['rows' => [
                [__('agentic::admin.fields.slug'), $source->slug],
                [__('agentic::admin.fields.driver'), $source->driver],
                [__('agentic::admin.fields.status'), __('agentic::admin.status.'.($source->status ?? 'draft'))],
                [__('agentic::admin.fields.description'), $source->description ?: '—'],
            ]])
            <form method="post" action="{{ route('agentic.admin.knowledge-sources.index-documents', $source->slug) }}" class="form-actions">
                @csrf
                <button type="submit" class="btn btn-primary">{{ __('agentic::admin.actions.index_documents') }}</button>
            </form>
        </div>
    </div>
@endsection
