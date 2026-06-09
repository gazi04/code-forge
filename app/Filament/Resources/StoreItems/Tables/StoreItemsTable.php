<?php

namespace App\Filament\Resources\StoreItems\Tables;

use App\Filament\Resources\StoreItems\Schemas\StoreItemForm;
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
                    ->square()
                    ->size(40),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->icon ?? ''),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StoreItemForm::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'title' => 'info',
                        'avatar' => 'success',
                        'streak_freeze' => 'warning',
                        'xp_boost' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('purchase_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StoreItemForm::PURCHASE_TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'permanent' => 'success',
                        'one_time' => 'warning',
                        'consumable' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('price_coins')
                    ->label('Price')
                    ->suffix(' coins')
                    ->sortable(),

                TextColumn::make('sold_count')
                    ->label('Sold / Stock')
                    ->formatStateUsing(fn (string $state, $record): string => $record->purchase_type === 'one_time'
                        ? "{$state} / {$record->stock_limit}"
                        : $state)
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(StoreItemForm::TYPES),

                SelectFilter::make('purchase_type')
                    ->label('Purchase Type')
                    ->options(StoreItemForm::PURCHASE_TYPES),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
