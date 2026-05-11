<?php

namespace App\Filament\Resources\Worlds;

use App\Filament\Resources\Worlds\Pages\CreateWorld;
use App\Filament\Resources\Worlds\Pages\EditWorld;
use App\Filament\Resources\Worlds\Pages\ListWorlds;
use App\Filament\Resources\Worlds\RelationManagers\CoursesRelationManager;
use App\Filament\Resources\Worlds\Schemas\WorldForm;
use App\Filament\Resources\Worlds\Tables\WorldsTable;
use App\Models\World;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorldResource extends Resource
{
    protected static ?string $model = World::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'World';

    public static function form(Schema $schema): Schema
    {
        return WorldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorldsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CoursesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorlds::route('/'),
            'create' => CreateWorld::route('/create'),
            'edit' => EditWorld::route('/{record}/edit'),
        ];
    }
}
