<?php

namespace Agentic\Filament\Resources\ToolResource\Pages;

use Agentic\Filament\Resources\ToolResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTools extends ManageRecords
{
    protected static string $resource = ToolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
