@php
    $providers = config('agentic.ai.providers', []);
    $selectedProvider = old('provider', $agent->provider ?? config('agentic.ai.provider'));
    $selectedModel = old('model', $agent->model ?? config('agentic.ai.model'));
@endphp

<label for="provider">{{ __('agentic::admin.fields.provider') }}</label>
<select name="provider" id="provider" required>
    @foreach ($providers as $key => $meta)
        <option value="{{ $key }}" @selected($selectedProvider === $key)>
            {{ is_array($meta) ? ($meta['label'] ?? $key) : $key }}
        </option>
    @endforeach
</select>

<label for="model">{{ __('agentic::admin.fields.model') }}</label>
<select name="model" id="model" required>
    @foreach ($providers as $key => $meta)
        @foreach ((is_array($meta) ? ($meta['models'] ?? []) : []) as $model)
            <option
                value="{{ $model }}"
                data-provider="{{ $key }}"
                @selected($selectedProvider === $key && $selectedModel === $model)
                @if($selectedProvider !== $key) hidden @endif
            >{{ $model }}</option>
        @endforeach
    @endforeach
</select>
<script>
(() => {
    const provider = document.getElementById('provider');
    const model = document.getElementById('model');
    if (!provider || !model) return;
    const sync = () => {
        const p = provider.value;
        let first = null;
        [...model.options].forEach((opt) => {
            const show = opt.dataset.provider === p;
            opt.hidden = !show;
            if (show && !first) first = opt;
        });
        const current = model.options[model.selectedIndex];
        if (!current || current.hidden) model.value = first?.value ?? '';
    };
    provider.addEventListener('change', sync);
    sync();
})();
</script>
