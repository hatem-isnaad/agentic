<?php

namespace Agentic\Console;

use Agentic\Console\Concerns\ConfirmsBeforeSave;
use Agentic\Models\Evaluation;
use Illuminate\Console\Command;

final class EvaluationCommand extends Command
{
    use ConfirmsBeforeSave;

    protected $signature = 'agentic:evaluation
        {action=create : create|list}
        {--score= : 1 to 5}
        {--agent=}
        {--conversation=}
        {--execution=}
        {--label=}
        {--notes=}';

    protected $description = 'Score a conversation or execution (asks one question at a time, then confirms)';

    public function handle(): int
    {
        return match ((string) $this->argument('action')) {
            'create' => $this->createScore(),
            'list' => $this->listScores(),
            default => $this->invalid(),
        };
    }

    private function createScore(): int
    {
        $interactive = $this->wantsPrompts($this->option('score') !== null ? (string) $this->option('score') : null);
        $score = $interactive ? (int) $this->choice('Score (1–5)', ['1', '2', '3', '4', '5'], 4) : (int) $this->option('score');
        $agent = $interactive ? (string) $this->ask('Agent slug (optional)', '') : (string) ($this->option('agent') ?: '');
        $conversation = $interactive ? (string) $this->ask('Conversation id (optional)', '') : (string) ($this->option('conversation') ?: '');
        $execution = $interactive ? (string) $this->ask('Execution id (optional)', '') : (string) ($this->option('execution') ?: '');
        $label = $interactive ? (string) $this->ask('Label (optional)', '') : (string) ($this->option('label') ?: '');
        $notes = $interactive ? (string) $this->ask('Notes (optional)', '') : (string) ($this->option('notes') ?: '');

        if ($score < 1 || $score > 5) {
            $this->error('Score must be 1–5.');

            return self::FAILURE;
        }

        if (! $this->summarizeAndConfirm([
            ['score', (string) $score],
            ['agent', $agent ?: '(none)'],
            ['conversation', $conversation ?: '(none)'],
        ], 'Save this evaluation?')) {
            return self::SUCCESS;
        }

        Evaluation::query()->create(array_filter([
            'score' => $score,
            'agent_slug' => $agent !== '' ? $agent : null,
            'conversation_id' => $conversation !== '' ? $conversation : null,
            'execution_id' => $execution !== '' ? $execution : null,
            'label' => $label !== '' ? $label : null,
            'notes' => $notes !== '' ? $notes : null,
        ], fn ($value) => $value !== null));

        $this->components->info('Saved evaluation score '.$score.'.');

        return self::SUCCESS;
    }

    private function listScores(): int
    {
        $rows = Evaluation::query()->orderByDesc('id')->limit(50)->get();
        if ($rows->isEmpty()) {
            $this->line('No evaluations.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'score', 'agent', 'conversation'],
            $rows->map(fn (Evaluation $row) => [$row->id, $row->score, $row->agent_slug, $row->conversation_id])->all(),
        );

        return self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('Action must be create or list.');

        return self::FAILURE;
    }
}
