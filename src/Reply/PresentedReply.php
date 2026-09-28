<?php

namespace Agentic\Reply;

final readonly class PresentedReply
{
    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public function __construct(public string $format, public string $html, public string $text, public array $blocks = []) {}
}
