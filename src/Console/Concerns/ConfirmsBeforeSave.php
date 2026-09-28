<?php

namespace Agentic\Console\Concerns;

trait ConfirmsBeforeSave
{
    protected function wantsPrompts(?string $anchor = null): bool
    {
        return ! $this->option('no-interaction') && ($anchor === null || $anchor === '');
    }

    /**
     * @param  list<array{0: string, 1: string|int|null}>  $rows
     */
    protected function summarizeAndConfirm(array $rows, string $question = 'Save this?'): bool
    {
        $this->table(
            ['Field', 'Value'],
            array_map(fn (array $row) => [$row[0], (string) ($row[1] ?? '')], $rows),
        );

        if ($this->option('no-interaction')) {
            return true;
        }

        if ($this->confirm($question, true)) {
            return true;
        }

        $this->warn('Cancelled.');

        return false;
    }

    /**
     * @return list<string>
     */
    protected function splitCsv(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
