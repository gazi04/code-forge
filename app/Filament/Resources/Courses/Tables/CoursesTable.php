<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn ($record) => Str::limit($record->description, 40)),

                TextColumn::make('world.name')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('age_tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'explorer' => 'success',
                        'builder' => 'warning',
                        'coder' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('difficulty')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('estimated_duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => "{$state}m")
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label('Status')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->label('Last Updated')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // We can add World or Age Tier filters here later
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
