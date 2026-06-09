<?php

namespace App\Filament\Resources\StoreItems;

use App\Filament\Resources\StoreItems\Pages\CreateStoreItem;
use App\Filament\Resources\StoreItems\Pages\EditStoreItem;
use App\Filament\Resources\StoreItems\Pages\ListStoreItems;
use App\Filament\Resources\StoreItems\RelationManagers\InventoryRelationManager;
use App\Filament\Resources\StoreItems\Schemas\StoreItemForm;
use App\Filament\Resources\StoreItems\Tables\StoreItemsTable;
use App\Models\StoreItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StoreItemResource extends Resource
{
    protected static ?string $model = StoreItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Game';

    public static function form(Schema $schema): Schema
    {
        return StoreItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InventoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoreItems::route('/'),
            'create' => CreateStoreItem::route('/create'),
            'edit' => EditStoreItem::route('/{record}/edit'),
        ];
    }
}
