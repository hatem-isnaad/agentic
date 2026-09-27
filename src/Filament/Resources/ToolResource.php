<?php

namespace Agentic\Filament\Resources;

use Agentic\Enums\Status;
use Agentic\Filament\Resources\ToolResource\Pages;
use Agentic\Models\Tool;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ToolResource extends Resource
{
    protected static ?string $model = Tool::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Agentic';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Textarea::make('description')->columnSpanFull(),
            Select::make('driver')
                ->options([
                    'http' => 'http',
                    'code' => 'code',
                    'mcp' => 'mcp',
                ])
                ->required(),
            Select::make('status')
                ->options(collect(Status::cases())->mapWithKeys(fn (Status $s) => [$s->value => $s->value])->all())
                ->required(),
            KeyValue::make('config')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('driver'),
                TextColumn::make('status'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTools::route('/'),
        ];
    }
}
