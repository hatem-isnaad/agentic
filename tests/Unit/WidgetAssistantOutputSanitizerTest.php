<?php

namespace Agentic\Tests\Unit;

use Agentic\Tests\TestCase;
use Agentic\Widget\Reply\WidgetAssistantOutputSanitizer;

final class WidgetAssistantOutputSanitizerTest extends TestCase
{
    public function test_strips_think_blocks(): void
    {
        $raw = "Hi there!";
        $this->assertSame('Hi there!', WidgetAssistantOutputSanitizer::clean($raw));
    }
}
