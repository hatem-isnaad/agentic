<form method="post" action="{{ route('agentic.admin.locale.update') }}" class="ag-locale" aria-label="{{ __('agentic::admin.locale.switch') }}">
    @csrf
    <label for="admin-locale" class="visually-hidden">{{ __('agentic::admin.locale.label') }}</label>
    <select name="locale" id="admin-locale" onchange="this.form.submit()">
        @foreach ($adminSupportedLocales as $code)
            <option value="{{ $code }}" @selected($adminLocale === $code)>
                {{ __('agentic::admin.locale.'.$code) }}
            </option>
        @endforeach
    </select>
</form>
