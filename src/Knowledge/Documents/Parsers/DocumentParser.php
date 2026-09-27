<?php

namespace Agentic\Knowledge\Documents\Parsers;

interface DocumentParser
{
    public function supports(string $format): bool;

    /**
     * @return list<string>
     */
    public function parse(mixed $payload): array;
}
