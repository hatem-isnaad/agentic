<?php

namespace Agentic\Filament\Resources\WorkflowResource\Pages;

use Agentic\Filament\Resources\WorkflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWorkflows extends ManageRecords
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
