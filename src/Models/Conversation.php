<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $table = 'agentic_conversations';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function latestChatMessage(): HasOne
    {
        return $this->hasOne(ConversationMessage::class, 'conversation_id')
            ->where('role', '!=', 'system')
            ->latestOfMany();
    }
}
