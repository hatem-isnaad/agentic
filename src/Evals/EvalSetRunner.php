<?php

namespace Agentic\Evals;

use Agentic\Agent\AgentResolver;
use Agentic\Context\RuntimeContext;
use Agentic\Exceptions\AgentNotFoundException;
use Agentic\Execution\AgentExecutionContext;
use Agentic\Models\EvalCase;
use Agentic\Models\EvalRun;
use Agentic\Models\EvalSet;
use Agentic\Runtime\AgentRuntime;

final class EvalSetRunner
{
    public function __construct(
        private AgentResolver $agents,
        private AgentRuntime $runtime,
    ) {}

    public function run(EvalSet $set): EvalRun
    {
        $run = EvalRun::query()->create([
            'eval_set_id' => $set->id,
            'agent_slug' => $set->agent_slug,
            'total' => $set->cases()->count(),
            'passed' => 0,
            'failed' => 0,
            'status' => 'running',
        ]);

        $passed = 0;
        $failed = 0;

        try {
            $agent = $this->agents->resolve($set->agent_slug);
        } catch (AgentNotFoundException $exception) {
            $run->fill(['status' => 'failed', 'error' => $exception->getMessage()])->save();

            return $run->fresh() ?? $run;
        }

        foreach ($set->cases()->orderBy('id')->get() as $case) {
            $ok = $this->runCase($agent, $case, $run);
            $ok ? $passed++ : $failed++;
        }

        $run->fill([
            'passed' => $passed,
            'failed' => $failed,
            'total' => $passed + $failed,
            'status' => 'completed',
        ])->save();

        return $run->fresh() ?? $run;
    }

    private function runCase(mixed $agent, EvalCase $case, EvalRun $run): bool
    {
        $result = $this->runtime->run($agent, new AgentExecutionContext(
            message: $case->question,
            metadata: ['channel' => 'eval'],
            runtime: new RuntimeContext(['channel' => 'eval']),
        ));

        $text = '';
        if (is_array($result->output)) {
            $text = (string) ($result->output['text'] ?? '');
        } elseif (is_string($result->output)) {
            $text = $result->output;
        }

        $needles = is_array($case->expect_contains) ? $case->expect_contains : [];
        $passed = $result->success;
        $error = $result->error;

        foreach ($needles as $needle) {
            if (! is_string($needle) || $needle === '') {
                continue;
            }
            if (! str_contains(mb_strtolower($text), mb_strtolower($needle))) {
                $passed = false;
                $error = $error ?: 'Missing expected text: '.$needle;
            }
        }

        $run->results()->create([
            'eval_case_id' => $case->id,
            'passed' => $passed,
            'output' => mb_substr($text, 0, 4000),
            'error' => $error,
        ]);

        return $passed;
    }
}
