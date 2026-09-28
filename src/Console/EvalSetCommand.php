<?php

namespace Agentic\Console;

use Agentic\Evals\EvalSetRunner;
use Agentic\Models\EvalSet;
use Illuminate\Console\Command;

final class EvalSetCommand extends Command
{
    protected $signature = 'agentic:eval
        {action=run : run|list}
        {slug? : Eval set slug}';

    protected $description = 'Run golden-question eval sets and show pass/fail';

    public function handle(EvalSetRunner $runner): int
    {
        return match ((string) $this->argument('action')) {
            'list' => $this->listSets(),
            'run' => $this->runSet($runner),
            default => $this->invalid(),
        };
    }

    private function listSets(): int
    {
        $rows = EvalSet::query()->withCount('cases')->orderBy('name')->get();
        if ($rows->isEmpty()) {
            $this->line('No eval sets. Create one in admin (/eval-sets).');

            return self::SUCCESS;
        }

        $this->table(
            ['slug', 'agent', 'cases'],
            $rows->map(fn (EvalSet $set) => [$set->slug, $set->agent_slug, $set->cases_count])->all(),
        );

        return self::SUCCESS;
    }

    private function runSet(EvalSetRunner $runner): int
    {
        $slug = (string) ($this->argument('slug') ?: $this->ask('Eval set slug'));
        $set = EvalSet::query()->where('slug', $slug)->with('cases')->first();
        if ($set === null) {
            $this->error('Eval set not found.');

            return self::FAILURE;
        }

        $run = $runner->run($set);
        $this->components->info("Passed {$run->passed} / {$run->total} ({$run->status}).");

        return $run->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('Action must be run or list.');

        return self::FAILURE;
    }
}
