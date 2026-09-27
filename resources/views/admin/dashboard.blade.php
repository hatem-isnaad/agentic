@extends('agentic::admin.layout')

@section('title', __('agentic::admin.dashboard.title'))

@section('content')
    <h1>{{ __('agentic::admin.dashboard.heading') }}</h1>
    <p>{{ __('agentic::admin.dashboard.intro') }}</p>

    <ul>
        <li>{{ __('agentic::admin.dashboard.stats.agents') }}: {{ $stats->agents }}</li>
        <li>{{ __('agentic::admin.dashboard.stats.skills') }}: {{ $stats->skills }}</li>
        <li>{{ __('agentic::admin.dashboard.stats.tools') }}: {{ $stats->tools }}</li>
        <li>{{ __('agentic::admin.dashboard.stats.knowledge_sources') }}: {{ $stats->knowledgeSources }}</li>
        <li>{{ __('agentic::admin.dashboard.stats.executions') }}: {{ $stats->executions }}</li>
        <li>{{ __('agentic::admin.dashboard.stats.conversations') }}: {{ $stats->conversations }}</li>
    </ul>
@endsection
