<header class="page-header">
    <div>
        <h1>{{ $title }}</h1>
        @if (! empty($lead))
            <p class="lead">{{ $lead }}</p>
        @endif
    </div>
    @if (! empty($actionUrl) && ! empty($actionLabel))
        <a href="{{ $actionUrl }}" class="btn btn-primary">{{ $actionLabel }}</a>
    @endif
</header>
