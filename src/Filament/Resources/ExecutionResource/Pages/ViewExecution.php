<?php

namespace Agentic\Filament\Resources\ExecutionResource\Pages;

use Agentic\Filament\Resources\ExecutionResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewExecution extends ViewRecord
{
    protected static string $resource = ExecutionResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('agent'),
            TextEntry::make('status'),
            TextEntry::make('conversation_id'),
            KeyValueEntry::make('input')->columnSpanFull(),
            KeyValueEntry::make('output')->columnSpanFull(),
            KeyValueEntry::make('metadata')->columnSpanFull(),
        ]);
    }
}
