<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConversationMessage extends Model
{
    protected $table = 'agentic_conversation_messages';

    protected $fillable = [
        'uuid',
        'conversation_id',
        'role',
        'content_html',
        'format',
        'tokens_in',
        'tokens_out',
        'tokens_total',
        'locale',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
