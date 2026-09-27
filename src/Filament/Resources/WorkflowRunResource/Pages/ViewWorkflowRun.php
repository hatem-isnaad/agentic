<?php

namespace Agentic\Filament\Resources\WorkflowRunResource\Pages;

use Agentic\Filament\Resources\WorkflowRunResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewWorkflowRun extends ViewRecord
{
    protected static string $resource = WorkflowRunResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('uuid'),
            TextEntry::make('workflow_slug'),
            TextEntry::make('status'),
            TextEntry::make('step_pointer'),
            TextEntry::make('approval_uuid'),
            TextEntry::make('error')->columnSpanFull(),
            KeyValueEntry::make('variables')->columnSpanFull(),
            KeyValueEntry::make('output')->columnSpanFull(),
            KeyValueEntry::make('trace')->columnSpanFull(),
        ]);
    }
}
