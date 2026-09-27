@extends('agentic::admin.layout')

@section('title', __('agentic::admin.dashboard.title'))

@section('content')
    @include('agentic::admin.partials.page-header', [
        'title' => __('agentic::admin.dashboard.heading'),
        'lead' => __('agentic::admin.dashboard.intro'),
    ])

    @include('agentic::admin.partials.dashboard-guide')

    <h2 style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;margin-bottom:1rem;">{{ __('agentic::admin.dashboard.stats_title') }}</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">{{ __('agentic::admin.dashboard.stats.agents') }}</div>
            <div class="value">{{ $stats->agents }}</div>
        </div>
        <div class="stat-card">
            <div class="label">{{ __('agentic::admin.dashboard.stats.skills') }}</div>
            <div class="value">{{ $stats->skills }}</div>
        </div>
        <div class="stat-card">
            <div class="label">{{ __('agentic::admin.dashboard.stats.tools') }}</div>
            <div class="value">{{ $stats->tools }}</div>
        </div>
        <div class="stat-card">
            <div class="label">{{ __('agentic::admin.dashboard.stats.knowledge_sources') }}</div>
            <div class="value">{{ $stats->knowledgeSources }}</div>
        </div>
        <div class="stat-card">
            <div class="label">{{ __('agentic::admin.dashboard.stats.workflows') }}</div>
            <div class="value">{{ $stats->workflows }}</div>
        </div>
        <div class="stat-card">
            <div class="label">{{ __('agentic::admin.dashboard.stats.executions') }}</div>
            <div class="value">{{ $stats->executions }}</div>
        </div>
        <div class="stat-card">
            <div class="label">{{ __('agentic::admin.dashboard.stats.conversations') }}</div>
            <div class="value">{{ $stats->conversations }}</div>
        </div>
    </div>
@endsection
