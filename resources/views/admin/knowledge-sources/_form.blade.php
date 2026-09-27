@php($source = $source ?? null)

<label for="name">{{ __('agentic::admin.fields.name') }}</label>
<input type="text" name="name" id="name" value="{{ old('name', $source->name ?? '') }}" required>

<label for="slug">{{ __('agentic::admin.fields.slug') }}</label>
<input type="text" name="slug" id="slug" value="{{ old('slug', $source->slug ?? '') }}" required @if($source) readonly @endif>

<label for="description">{{ __('agentic::admin.fields.description') }}</label>
<textarea name="description" id="description">{{ old('description', $source->description ?? '') }}</textarea>

<label for="driver">{{ __('agentic::admin.fields.driver') }}</label>
<select name="driver" id="driver">
    @foreach (['array', 'vector'] as $driver)
        <option value="{{ $driver }}" @selected(old('driver', $source->driver ?? 'array') === $driver)>{{ $driver }}</option>
    @endforeach
</select>

@include('agentic::admin.partials.status-select', ['selected' => $source->status ?? 'draft'])
