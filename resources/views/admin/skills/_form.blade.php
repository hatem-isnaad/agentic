@php($skill = $skill ?? null)

<label for="name">{{ __('agentic::admin.fields.name') }}</label>
<input type="text" name="name" id="name" value="{{ old('name', $skill->name ?? '') }}" required>

<label for="slug">{{ __('agentic::admin.fields.slug') }}</label>
<input type="text" name="slug" id="slug" value="{{ old('slug', $skill->slug ?? '') }}" required @if($skill) readonly @endif>

<label for="description">{{ __('agentic::admin.fields.description') }}</label>
<textarea name="description" id="description">{{ old('description', $skill->description ?? '') }}</textarea>

<label for="instructions">{{ __('agentic::admin.fields.instructions') }}</label>
<textarea name="instructions" id="instructions">{{ old('instructions', $skill->instructions ?? '') }}</textarea>

@include('agentic::admin.partials.status-select', ['selected' => $skill->status ?? 'draft'])

<label for="tools">{{ __('agentic::admin.fields.tools') }}</label>
<input type="text" name="tools" id="tools" value="{{ old('tools', isset($skill) ? implode(', ', $skill->tools) : '') }}">
