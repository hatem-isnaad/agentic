<?php

namespace Agentic\Filament\Resources;

use Agentic\Filament\Resources\WorkflowRunResource\Pages;
use Agentic\Models\WorkflowRun;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkflowRunResource extends Resource
{
    protected static ?string $model = WorkflowRun::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-play-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Agentic';

    protected static ?string $navigationLabel = 'Workflow runs';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')->label('Run ID')->searchable()->copyable(),
                TextColumn::make('workflow_slug')->searchable(),
                TextColumn::make('status'),
                TextColumn::make('step_pointer'),
                TextColumn::make('approval_uuid')->label('Approval'),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowRuns::route('/'),
            'view' => Pages\ViewWorkflowRun::route('/{record}'),
        ];
    }
}
