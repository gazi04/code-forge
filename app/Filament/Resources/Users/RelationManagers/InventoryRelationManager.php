<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryRelationManager extends RelationManager
{
    protected static string $relationship = 'inventory';

    protected static ?string $title = 'Inventory';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('acquired_at', 'desc')
            ->columns([
                TextColumn::make('storeItem.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('storeItem.type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('quantity')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('acquired_at')
                    ->label('Acquired')
                    ->dateTime()
                    ->sortable(),
            ])
            ->groupedBulkActions([]);
    }
}
