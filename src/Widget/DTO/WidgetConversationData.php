<?php

namespace Agentic\Widget\DTO;

final readonly class WidgetConversationData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(public string $agent, public WidgetIdentity $identity, public array $metadata = []) {}
}
