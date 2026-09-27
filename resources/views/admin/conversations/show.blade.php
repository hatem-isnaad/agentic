@extends('agentic::admin.layout')

@section('title', __('agentic::admin.conversations.show_title'))

@section('content')
    @include('agentic::admin.partials.page-header', ['title' => __('agentic::admin.conversations.show_title')])

    <div class="card">
        <div class="card-body">
            @include('agentic::admin.partials.detail-rows', ['rows' => [
                [__('agentic::admin.table.id'), (string) $conversation->id],
                [__('agentic::admin.fields.agent'), $conversation->agent_slug ?? '—'],
                [__('agentic::admin.fields.user_id'), $conversation->user_id ?? '—'],
                [__('agentic::admin.fields.guest_id'), $conversation->guest_id ?? '—'],
                [__('agentic::admin.fields.updated_at'), optional($conversation->updated_at)->toDateTimeString() ?? '—'],
            ]])

            <h2 style="font-size:1rem;margin:1.5rem 0 0.75rem;">{{ __('agentic::admin.conversations.messages') }}</h2>
            <div class="message-list">
                @forelse ($messages as $message)
                    <article class="message-item">
                        <div class="role">{{ __('agentic::admin.fields.role') }}: {{ $message->role }}</div>
                        <div>{!! $message->content_html ?? e($message->content ?? '') !!}</div>
                    </article>
                @empty
                    <p>{{ __('agentic::admin.empty.messages') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
