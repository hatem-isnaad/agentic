<?php

namespace Agentic\Admin\Services;

use Agentic\Admin\DTO\UsageSummaryData;
use Agentic\Models\Execution;

final class UsageAdminService
{
    public function summary(int $limit = 400): UsageSummaryData
    {
        $rows = Execution::query()
            ->latest('id')
            ->limit(max(1, min(2000, $limit)))
            ->get(['uuid', 'agent', 'output', 'metadata', 'created_at']);

        $tokensIn = 0;
        $tokensOut = 0;
        $cost = 0.0;
        $byAgent = [];
        $byDay = [];
        $recent = [];
        $currency = (string) config('agentic.usage.currency', 'USD');

        foreach ($rows as $row) {
            $usage = $this->usageFrom($row->output);
            $in = $usage['in'];
            $out = $usage['out'];
            $model = $this->modelFrom($row->metadata);
            $usd = $this->estimateUsd($model, $in, $out);

            $tokensIn += $in;
            $tokensOut += $out;
            $cost += $usd;

            $agent = (string) ($row->agent ?? 'unknown');
            $byAgent[$agent] ??= ['agent' => $agent, 'executions' => 0, 'tokens_in' => 0, 'tokens_out' => 0, 'estimated_usd' => 0.0];
            $byAgent[$agent]['executions']++;
            $byAgent[$agent]['tokens_in'] += $in;
            $byAgent[$agent]['tokens_out'] += $out;
            $byAgent[$agent]['estimated_usd'] += $usd;

            $day = optional($row->created_at)?->toDateString() ?? 'unknown';
            $byDay[$day] ??= ['date' => $day, 'executions' => 0, 'tokens_in' => 0, 'tokens_out' => 0, 'estimated_usd' => 0.0];
            $byDay[$day]['executions']++;
            $byDay[$day]['tokens_in'] += $in;
            $byDay[$day]['tokens_out'] += $out;
            $byDay[$day]['estimated_usd'] += $usd;

            if (count($recent) < 20) {
                $recent[] = [
                    'id' => $row->uuid,
                    'agent' => $agent,
                    'model' => $model,
                    'tokens_in' => $in,
                    'tokens_out' => $out,
                    'estimated_usd' => round($usd, 6),
                    'created_at' => optional($row->created_at)?->toISOString(),
                ];
            }
        }

        foreach ($byAgent as &$row) {
            $row['estimated_usd'] = round((float) $row['estimated_usd'], 4);
        }
        unset($row);

        foreach ($byDay as &$dayRow) {
            $dayRow['estimated_usd'] = round((float) $dayRow['estimated_usd'], 4);
        }
        unset($dayRow);

        usort($byAgent, fn (array $a, array $b) => $b['tokens_in'] + $b['tokens_out'] <=> $a['tokens_in'] + $a['tokens_out']);
        krsort($byDay);

        $count = max(1, $rows->count());
        $avg = $rows->count() > 0 ? $cost / $count : 0.0;

        return new UsageSummaryData(
            executions: $rows->count(),
            tokensIn: $tokensIn,
            tokensOut: $tokensOut,
            tokensTotal: $tokensIn + $tokensOut,
            estimatedUsd: round($cost, 4),
            currency: $currency,
            byAgent: array_values($byAgent),
            byDay: array_values($byDay),
            recent: $recent,
            avgUsdPerMessage: round($avg, 6),
            messagesPer20Usd: $avg > 0 ? (int) floor(20 / $avg) : 0,
        );
    }

    /**
     * @return array{in: int, out: int}
     */
    private function usageFrom(mixed $output): array
    {
        $usage = is_array($output) && is_array($output['usage'] ?? null) ? $output['usage'] : [];

        if (is_object($output) && isset($output->usage) && is_array($output->usage)) {
            $usage = $output->usage;
        }

        $in = (int) ($usage['input_tokens'] ?? $usage['tokens_in'] ?? $usage['prompt_tokens'] ?? 0);
        $out = (int) ($usage['output_tokens'] ?? $usage['tokens_out'] ?? $usage['completion_tokens'] ?? 0);

        return ['in' => $in, 'out' => $out];
    }

    private function modelFrom(mixed $metadata): string
    {
        if (! is_array($metadata)) {
            return '';
        }

        return trim((string) ($metadata['model'] ?? ''));
    }

    private function estimateUsd(string $model, int $tokensIn, int $tokensOut): float
    {
        $models = (array) config('agentic.usage.models', []);
        $rates = is_array($models[$model] ?? null) ? $models[$model] : (array) config('agentic.usage.default', []);
        $inputPerM = (float) ($rates['input'] ?? 0);
        $outputPerM = (float) ($rates['output'] ?? 0);

        return ($tokensIn / 1_000_000) * $inputPerM + ($tokensOut / 1_000_000) * $outputPerM;
    }
}
