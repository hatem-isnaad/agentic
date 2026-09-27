<?php

namespace Agentic\Filament\Resources\KnowledgeSourceResource\Pages;

use Agentic\Filament\Resources\KnowledgeSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKnowledgeSources extends ManageRecords
{
    protected static string $resource = KnowledgeSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
