<?php

namespace Agentic\Filament\Resources;

use Agentic\Filament\Resources\ExecutionResource\Pages;
use Agentic\Models\Execution;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExecutionResource extends Resource
{
    protected static ?string $model = Execution::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Agentic';

    protected static ?string $navigationLabel = 'Executions';

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
                TextColumn::make('id')->sortable(),
                TextColumn::make('agent')->searchable(),
                TextColumn::make('status'),
                TextColumn::make('conversation_id'),
                TextColumn::make('started_at')->dateTime(),
                TextColumn::make('completed_at')->dateTime(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExecutions::route('/'),
            'view' => Pages\ViewExecution::route('/{record}'),
        ];
    }
}
