@php($source = $source ?? null)

<label for="name">Name</label>
<input type="text" name="name" id="name" value="{{ old('name', $source->name ?? '') }}" required>

<label for="slug">Slug</label>
<input type="text" name="slug" id="slug" value="{{ old('slug', $source->slug ?? '') }}" required @if($source) readonly @endif>

<label for="description">Description</label>
<textarea name="description" id="description">{{ old('description', $source->description ?? '') }}</textarea>

<label for="driver">Driver</label>
<select name="driver" id="driver">
    @foreach (['array', 'vector'] as $driver)
        <option value="{{ $driver }}" @selected(old('driver', $source->driver ?? 'array') === $driver)>{{ $driver }}</option>
    @endforeach
</select>

@include('agentic::admin.partials.status-select', ['selected' => $source->status ?? 'draft'])
