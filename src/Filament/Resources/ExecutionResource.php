<?php

namespace Agentic\Filament\Resources;

use Agentic\Filament\Resources\ExecutionResource\Pages;
use Agentic\Models\Execution;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExecutionResource extends Resource
{
    protected static ?string $model = Execution::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-play-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Agentic';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->limit(12),
                TextColumn::make('agent'),
                TextColumn::make('status'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExecutions::route('/'),
            'view' => Pages\ViewExecution::route('/{record}'),
        ];
    }
}
