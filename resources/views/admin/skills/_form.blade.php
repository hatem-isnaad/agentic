@php($skill = $skill ?? null)

<label for="name">Name</label>
<input type="text" name="name" id="name" value="{{ old('name', $skill->name ?? '') }}" required>

<label for="slug">Slug</label>
<input type="text" name="slug" id="slug" value="{{ old('slug', $skill->slug ?? '') }}" required @if($skill) readonly @endif>

<label for="description">Description</label>
<textarea name="description" id="description">{{ old('description', $skill->description ?? '') }}</textarea>

<label for="instructions">Instructions</label>
<textarea name="instructions" id="instructions">{{ old('instructions', $skill->instructions ?? '') }}</textarea>

@include('agentic::admin.partials.status-select', ['selected' => $skill->status ?? 'draft'])

<label for="tools">Tools (comma-separated slugs)</label>
<input type="text" name="tools" id="tools" value="{{ old('tools', isset($skill) ? implode(', ', $skill->tools) : '') }}">
