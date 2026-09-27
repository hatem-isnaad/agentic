<label for="status">Status</label>
<select name="status" id="status">
    @foreach (['draft', 'published', 'archived'] as $option)
        <option value="{{ $option }}" @selected(old('status', $selected ?? 'draft') === $option)>{{ $option }}</option>
    @endforeach
</select>
