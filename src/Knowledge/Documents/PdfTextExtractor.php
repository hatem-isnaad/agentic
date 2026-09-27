<?php

namespace Agentic\Knowledge\Documents;

interface PdfTextExtractor
{
    public function extract(string $binary): string;
}
