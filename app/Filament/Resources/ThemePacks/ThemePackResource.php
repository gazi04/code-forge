<?php

namespace App\Filament\Resources\ThemePacks;

use App\Filament\Resources\ThemePacks\Pages\CreateThemePack;
use App\Filament\Resources\ThemePacks\Pages\EditThemePack;
use App\Filament\Resources\ThemePacks\Pages\ListThemePacks;
use App\Filament\Resources\ThemePacks\Schemas\ThemePackForm;
use App\Filament\Resources\ThemePacks\Tables\ThemePacksTable;
use App\Models\ThemePack;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ThemePackResource extends Resource
{
    protected static ?string $model = ThemePack::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ThemePackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThemePacksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThemePacks::route('/'),
            'create' => CreateThemePack::route('/create'),
            'edit' => EditThemePack::route('/{record}/edit'),
        ];
    }
}
