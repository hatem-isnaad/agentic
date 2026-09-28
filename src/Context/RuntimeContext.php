<?php

namespace Agentic\Context;

use Agentic\Agent\AgentDefinition;

/**
 * Controlled runtime information available during Agent execution.
 */
final class RuntimeContext
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        private array $attributes = [],
    ) {}

    public function user(): mixed
    {
        return $this->get('user');
    }

    public function agent(): ?AgentDefinition
    {
        $agent = $this->get('agent');

        return $agent instanceof AgentDefinition ? $agent : null;
    }

    public function conversation(): mixed
    {
        return $this->get('conversation');
    }

    public function request(): mixed
    {
        return $this->get('request');
    }

    public function locale(): ?string
    {
        $locale = $this->get('locale');

        return is_string($locale) ? $locale : null;
    }

    public function timezone(): ?string
    {
        $timezone = $this->get('timezone');

        return is_string($timezone) ? $timezone : null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    public function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function merge(array $attributes): self
    {
        $clone = clone $this;
        $clone->attributes = array_merge($clone->attributes, $attributes);

        return $clone;
    }
}
