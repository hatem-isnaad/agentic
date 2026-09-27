<?php

namespace Agentic\Agent;

/**
 * Structured voice for an agent (name, gender, language, dialect, tone).
 *
 * Stored on the agent as config.persona. Empty values are omitted at runtime.
 */
final readonly class AgentPersona
{
    public function __construct(
        public ?string $displayName = null,
        public ?string $gender = null,
        public ?string $language = null,
        public ?string $dialect = null,
        public ?string $tone = null,
        public ?string $notes = null,
    ) {}

    public static function fromAgent(AgentDefinition $agent): self
    {
        $config = $agent->metadata['config'] ?? [];
        $persona = is_array($config) ? ($config['persona'] ?? null) : null;

        return self::fromConfig($persona);
    }

    public static function fromConfig(mixed $raw): self
    {
        if (! is_array($raw)) {
            return new self();
        }

        return new self(
            displayName: self::string($raw['display_name'] ?? $raw['name'] ?? null),
            gender: self::allowed($raw['gender'] ?? null, config('agentic.persona.genders', [])),
            language: self::allowed($raw['language'] ?? null, config('agentic.persona.languages', [])),
            dialect: self::allowed($raw['dialect'] ?? null, config('agentic.persona.dialects', [])),
            tone: self::allowed($raw['tone'] ?? null, config('agentic.persona.tones', [])),
            notes: self::string($raw['notes'] ?? null),
        );
    }

    public function isEmpty(): bool
    {
        return $this->displayName === null
            && $this->gender === null
            && $this->language === null
            && $this->dialect === null
            && $this->tone === null
            && $this->notes === null;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'display_name' => $this->displayName,
            'gender' => $this->gender,
            'language' => $this->language,
            'dialect' => $this->dialect,
            'tone' => $this->tone,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);
    }

    private static function string(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function allowed(mixed $value, array $allowed): ?string
    {
        $string = self::string($value);
        if ($string === null) {
            return null;
        }

        $allowed = array_values(array_filter($allowed, fn ($item) => is_string($item)));

        return in_array($string, $allowed, true) ? $string : null;
    }
}
