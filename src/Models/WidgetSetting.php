<?php

namespace Agentic\Models;

use Illuminate\Database\Eloquent\Model;

final class WidgetSetting extends Model
{
    protected $table = 'agentic_widget_settings';

    protected $fillable = [
        'agent_slug',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }
}
