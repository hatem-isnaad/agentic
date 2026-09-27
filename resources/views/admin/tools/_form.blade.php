@php($tool = $tool ?? null)

<label for="name">{{ __('agentic::admin.fields.name') }}</label>
<input type="text" name="name" id="name" value="{{ old('name', $tool->name ?? '') }}" required>

<label for="slug">{{ __('agentic::admin.fields.slug') }}</label>
<input type="text" name="slug" id="slug" value="{{ old('slug', $tool->slug ?? '') }}" required @if($tool) readonly @endif>

<label for="driver">{{ __('agentic::admin.fields.driver') }}</label>
<input type="text" name="driver" id="driver" value="{{ old('driver', $tool->driver ?? 'http') }}" required>

<label for="description">{{ __('agentic::admin.fields.description') }}</label>
<textarea name="description" id="description">{{ old('description', $tool->description ?? '') }}</textarea>

@include('agentic::admin.partials.status-select', ['selected' => $tool->status ?? 'draft'])
