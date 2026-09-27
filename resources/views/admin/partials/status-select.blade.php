<label for="status">{{ __('agentic::admin.fields.status') }}</label>
<select name="status" id="status">
    @foreach (['draft', 'published', 'archived'] as $option)
        <option value="{{ $option }}" @selected(old('status', $selected ?? 'draft') === $option)>
            {{ __('agentic::admin.status.'.$option) }}
        </option>
    @endforeach
</select>
