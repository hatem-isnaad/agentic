<?php

namespace Agentic\Admin\DTO;

final readonly class UsageSummaryData
{
    /**
     * @param  list<array<string, mixed>>  $byAgent
     * @param  list<array<string, mixed>>  $byDay
     * @param  list<array<string, mixed>>  $recent
     */
    public function __construct(
        public int $executions,
        public int $tokensIn,
        public int $tokensOut,
        public int $tokensTotal,
        public float $estimatedUsd,
        public string $currency,
        public array $byAgent,
        public array $byDay,
        public array $recent,
        public float $avgUsdPerMessage,
        public int $messagesPer20Usd,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'executions' => $this->executions,
            'tokens_in' => $this->tokensIn,
            'tokens_out' => $this->tokensOut,
            'tokens_total' => $this->tokensTotal,
            'estimated_usd' => $this->estimatedUsd,
            'currency' => $this->currency,
            'by_agent' => $this->byAgent,
            'by_day' => $this->byDay,
            'avg_usd_per_message' => $this->avgUsdPerMessage,
            'messages_per_20_usd' => $this->messagesPer20Usd,
            'recent' => $this->recent,
        ];
    }
}
