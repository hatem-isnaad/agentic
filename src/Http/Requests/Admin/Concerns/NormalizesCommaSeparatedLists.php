<?php

namespace Agentic\Http\Requests\Admin\Concerns;

trait NormalizesCommaSeparatedLists
{
    /**
     * @param  list<string>  $fields
     */
    protected function normalizeCommaSeparatedLists(array $fields): void
    {
        foreach ($fields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $this->merge([
                $field => array_values(array_filter(array_map('trim', explode(',', $value)))),
            ]);
        }
    }
}
