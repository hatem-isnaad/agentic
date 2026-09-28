<?php

namespace Agentic\Tests\Unit;

use Agentic\Channels\WhatsApp\MetaWebhookParser;
use Agentic\Tests\TestCase;

final class MetaWebhookParserTest extends TestCase
{
    public function test_extracts_text_messages_and_skips_other_types(): void
    {
        $messages = (new MetaWebhookParser)->inboundTexts([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => '109876'],
                        'messages' => [
                            ['id' => 'wamid.1', 'from' => '20100000000', 'type' => 'text', 'text' => ['body' => 'Hello']],
                            ['id' => 'wamid.2', 'from' => '20100000000', 'type' => 'image'],
                        ],
                    ],
                ]],
            ]],
        ]);

        $this->assertCount(1, $messages);
        $this->assertSame('109876', $messages[0]['phone_number_id']);
        $this->assertSame('20100000000', $messages[0]['from']);
        $this->assertSame('Hello', $messages[0]['text']);
    }
}
