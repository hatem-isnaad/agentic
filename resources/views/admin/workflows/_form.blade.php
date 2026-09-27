<label for="name">{{ __('agentic::admin.fields.name') }}</label>
<input type="text" name="name" id="name" value="{{ old('name', $workflow?->name) }}" required>

<label for="slug">{{ __('agentic::admin.fields.slug') }}</label>
<input type="text" name="slug" id="slug" value="{{ old('slug', $workflow?->slug) }}" required @readonly(isset($workflow))>

<label for="description">{{ __('agentic::admin.fields.description') }}</label>
<textarea name="description" id="description" rows="3">{{ old('description', $workflow?->description) }}</textarea>

@include('agentic::admin.partials.status-select', ['selected' => $workflow?->status ?? 'draft'])

<label for="steps_json">{{ __('agentic::admin.fields.steps') }}</label>
<p class="lead" style="margin:0.25rem 0 0.5rem;font-size:0.85rem;color:var(--ag-muted);">{{ __('agentic::admin.workflows.steps_hint') }}</p>
<textarea name="steps_json" id="steps_json" class="code" required>{{ old('steps_json', $stepsJson ?? '') }}</textarea>
