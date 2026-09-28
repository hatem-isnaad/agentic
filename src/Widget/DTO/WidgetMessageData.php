<?php

namespace Agentic\Widget\DTO;

final readonly class WidgetMessageData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(public string $agent, public string $message, public WidgetIdentity $identity, public ?string $conversationId = null, public array $metadata = []) {}
}
