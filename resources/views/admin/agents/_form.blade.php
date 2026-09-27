@php($agent = $agent ?? null)

<label for="name">Name</label>
<input type="text" name="name" id="name" value="{{ old('name', $agent->name ?? '') }}" required>

<label for="slug">Slug</label>
<input type="text" name="slug" id="slug" value="{{ old('slug', $agent->slug ?? '') }}" required @if($agent) readonly @endif>

<label for="description">Description</label>
<textarea name="description" id="description">{{ old('description', $agent->description ?? '') }}</textarea>

<label for="instructions">Instructions</label>
<textarea name="instructions" id="instructions">{{ old('instructions', $agent->instructions ?? '') }}</textarea>

@include('agentic::admin.partials.status-select', ['selected' => $agent->status ?? 'draft'])

<label for="provider">Provider</label>
<input type="text" name="provider" id="provider" value="{{ old('provider', $agent->provider ?? '') }}">

<label for="model">Model</label>
<input type="text" name="model" id="model" value="{{ old('model', $agent->model ?? '') }}">

<label for="skills">Skills (comma-separated slugs)</label>
<input type="text" name="skills" id="skills" value="{{ old('skills', isset($agent) ? implode(', ', $agent->skills) : '') }}">

<label for="tools">Tools (comma-separated slugs)</label>
<input type="text" name="tools" id="tools" value="{{ old('tools', isset($agent) ? implode(', ', $agent->tools) : '') }}">
