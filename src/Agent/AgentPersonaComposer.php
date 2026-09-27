<?php

namespace Agentic\Agent;

/**
 * Turns AgentPersona into a short instruction block for the model.
 */
final class AgentPersonaComposer
{
    public function compose(string $instructions, AgentPersona $persona): string
    {
        $block = $this->block($persona);
        $base = trim($instructions);

        if ($block === '') {
            return $base;
        }

        return $base === '' ? $block : $base."\n\n".$block;
    }

    public function block(AgentPersona $persona): string
    {
        if ($persona->isEmpty()) {
            return '';
        }

        $lines = ['Persona (follow on every reply):'];

        if ($persona->displayName !== null) {
            $who = $persona->displayName;
            if ($persona->gender === 'female') {
                $lines[] = "- You are {$who}, a female assistant. Introduce yourself as {$who}. Use feminine first-person Arabic (أنا مساعدة، أقدر أساعدك) when speaking Arabic.";
            } elseif ($persona->gender === 'male') {
                $lines[] = "- You are {$who}, a male assistant. Introduce yourself as {$who}. Use masculine first-person Arabic (أنا مساعد، أقدر أساعدك) when speaking Arabic.";
            } else {
                $lines[] = "- You are {$who}. Introduce yourself as {$who}. Do not invent another name.";
            }
        } elseif ($persona->gender === 'female') {
            $lines[] = '- You are a female assistant. Use feminine first-person Arabic when speaking Arabic.';
        } elseif ($persona->gender === 'male') {
            $lines[] = '- You are a male assistant. Use masculine first-person Arabic when speaking Arabic.';
        }

        $lines[] = $this->languageLine($persona);
        $tone = $this->toneLine($persona);
        if ($tone !== null) {
            $lines[] = $tone;
        }

        if ($persona->notes !== null) {
            $lines[] = '- Extra voice notes: '.$persona->notes;
        }

        return implode("\n", array_values(array_filter($lines)));
    }

    private function languageLine(AgentPersona $persona): string
    {
        $dialect = match ($persona->dialect) {
            'saudi' => 'everyday Saudi Arabic (لهجة سعودية). Use natural Saudi phrasing (وش، أبي، زين، إن شاء الله). Avoid Egyptian or Levantine slang unless the user uses it.',
            'egyptian' => 'Egyptian Arabic (عامية مصرية). Use natural Egyptian phrasing (إزيك، عامل إيه، ماشي). Avoid Gulf slang unless the user uses it.',
            'gulf' => 'Gulf Arabic (خليجية). Use natural Khaleeji phrasing. Avoid Egyptian slang unless the user uses it.',
            'levant' => 'Levantine Arabic (شامي). Use natural Levantine phrasing. Avoid Gulf or Egyptian slang unless the user uses it.',
            'msa' => 'clear Modern Standard Arabic (فصحى مبسطة), not heavy classical and not a regional slang unless the user uses it.',
            default => null,
        };

        return match ($persona->language) {
            'ar' => $dialect !== null
                ? "- Speak {$dialect} Reply in Arabic unless the user writes in English."
                : '- Speak Arabic. Reply in English only if the user writes in English.',
            'en' => '- Speak English. Keep Arabic only for names or if the user writes in Arabic.',
            'bilingual' => $dialect !== null
                ? "- Reply in the user's language. When Arabic, speak {$dialect}"
                : "- Reply in the user's language (Arabic or English). If mixed, prefer Arabic.",
            default => $dialect !== null
                ? "- When speaking Arabic, use {$dialect}"
                : '- Match the user\'s language.',
        };
    }

    private function toneLine(AgentPersona $persona): ?string
    {
        return match ($persona->tone) {
            'friendly' => '- Tone: friendly, easy, and warm — like a helpful colleague. Short sentences. Not stiff.',
            'formal' => '- Tone: formal and polite. Avoid slang and jokes unless the user starts them.',
            'casual' => '- Tone: casual and relaxed, like a chat with a friend. Still clear and respectful.',
            'professional' => '- Tone: professional and concise. Business-like, no filler.',
            'warm' => '- Tone: warm and reassuring. Acknowledge feelings, then help.',
            default => null,
        };
    }
}
