<?php

namespace App\Filament\Resources\StoreItems\Tables;

use App\Enums\PurchaseType;
use App\Enums\StoreItemType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StoreItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->square()
                    ->size(40),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->icon ?? ''),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('purchase_type')
                    ->badge(),

                TextColumn::make('price_coins')
                    ->label('Price')
                    ->suffix(' coins')
                    ->sortable(),

                TextColumn::make('sold_count')
                    ->label('Sold / Stock')
                    ->formatStateUsing(fn (string $state, $record): string => $record->purchase_type === PurchaseType::OneTime
                        ? "{$state} / {$record->stock_limit}"
                        : $state)
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(StoreItemType::class),

                SelectFilter::make('purchase_type')
                    ->label('Purchase Type')
                    ->options(PurchaseType::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
