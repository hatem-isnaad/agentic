<?php

namespace Agentic\Console;

use Agentic\Contracts\Repositories\WorkflowRunRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class PruneWorkflowRunsCommand extends Command
{
    protected $signature = 'agentic:prune-workflow-runs
                            {--days= : Retention in days (default: config agentic.workflows.runs.retention_days)}
                            {--dry-run : Report count only}';

    protected $description = 'Delete workflow run records older than the retention window';

    public function handle(WorkflowRunRepository $runs): int
    {
        $days = (int) ($this->option('days') ?? config('agentic.workflows.runs.retention_days', 90));

        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($days);

        if ((bool) $this->option('dry-run')) {
            $this->info("Would delete workflow runs updated before {$cutoff->toDateTimeString()} ({$days} days).");

            return self::SUCCESS;
        }

        $deleted = $runs->deleteOlderThan($cutoff);

        $this->info("Deleted {$deleted} workflow run(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
