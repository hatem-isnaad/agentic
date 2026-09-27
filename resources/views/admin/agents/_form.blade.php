@php($agent = $agent ?? null)

<label for="name">{{ __('agentic::admin.fields.name') }}</label>
<input type="text" name="name" id="name" value="{{ old('name', $agent->name ?? '') }}" required>

<label for="slug">{{ __('agentic::admin.fields.slug') }}</label>
<input type="text" name="slug" id="slug" value="{{ old('slug', $agent->slug ?? '') }}" required @if($agent) readonly @endif>

<label for="description">{{ __('agentic::admin.fields.description') }}</label>
<textarea name="description" id="description">{{ old('description', $agent->description ?? '') }}</textarea>

<label for="instructions">{{ __('agentic::admin.fields.instructions') }}</label>
<textarea name="instructions" id="instructions">{{ old('instructions', $agent->instructions ?? '') }}</textarea>

@include('agentic::admin.partials.status-select', ['selected' => $agent->status ?? 'draft'])

@include('agentic::admin.partials.ai-provider-fields', ['agent' => $agent ?? null])

<label for="skills">{{ __('agentic::admin.fields.skills') }}</label>
<input type="text" name="skills" id="skills" value="{{ old('skills', isset($agent) ? implode(', ', $agent->skills) : '') }}">

<label for="tools">{{ __('agentic::admin.fields.tools') }}</label>
<input type="text" name="tools" id="tools" value="{{ old('tools', isset($agent) ? implode(', ', $agent->tools) : '') }}">
