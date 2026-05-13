<?php

namespace App\Filament\Resources\ThemePacks\Tables;

use App\Models\ThemePack;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class ThemePacksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('identifier')
                    ->label('Identifier')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->searchable(),

                // Three palette swatches rendered via a Blade view
                ViewColumn::make('config.palette')
                    ->label('Palette')
                    ->view('filament.tables.columns.palette-swatches'),

                TextColumn::make('config.ui.card_style')
                    ->label('Card style')
                    ->badge(),

                TextColumn::make('worlds_count')
                    ->label('Worlds')
                    ->counts('worlds')
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),

                DeleteAction::make()
                    ->before(function (ThemePack $record, DeleteAction $action) {
                        if ($record->worlds()->exists()) {
                            $action->cancel();
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete theme pack')
                                ->body("This theme is used by {$record->worlds()->count()} world(s). Reassign them first.")
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
