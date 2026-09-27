<?php

namespace Agentic\Reply;

use Agentic\Context\RuntimeContext;

/**
 * Tells the model how to write for this channel. Web gets Markdown → HTML.
 * WhatsApp/Messenger get their own markers — not HTML.
 */
final class ChannelPresentationComposer
{
    public function compose(string $instructions, ?RuntimeContext $runtime): string
    {
        $block = $this->block(ReplyChannel::fromRuntime($runtime?->get('channel')));
        $base = trim($instructions);

        if ($block === '') {
            return $base;
        }

        return $base === '' ? $block : $base."\n\n".$block;
    }

    public function block(ReplyChannel $channel): string
    {
        return match ($channel) {
            ReplyChannel::Widget, ReplyChannel::Admin, ReplyChannel::Web => implode("\n", [
                'Channel: web chat. The UI renders Markdown as HTML (headings, bold, italic, lists, tables, code).',
                '- Write structured Markdown. Use **bold**, *italic*, lists, and tables when they help.',
                '- Do not emit raw HTML tags, inline CSS, or scripts.',
                '- Do not use WhatsApp markers (*bold* or _italic_). This is a web page, not WhatsApp or Messenger.',
            ]),
            ReplyChannel::WhatsApp => implode("\n", [
                'Channel: WhatsApp. Reply in WhatsApp text only.',
                '- Bold: *text*. Italic: _text_. Strike: ~text~. Code: ```text```.',
                '- No HTML, no Markdown tables, no **double asterisks**. Keep replies short.',
            ]),
            ReplyChannel::Messenger => implode("\n", [
                'Channel: Messenger. Reply in plain chat text.',
                '- You may use simple *bold* and _italic_. No HTML and no tables.',
            ]),
        };
    }
}
