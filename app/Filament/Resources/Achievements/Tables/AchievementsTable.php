<?php

namespace App\Filament\Resources\Achievements\Tables;

use App\Filament\Resources\Achievements\Schemas\AchievementForm;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AchievementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Art')
                    ->disk('public')
                    ->square()
                    ->size(40),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('metric_type')
                    ->label('Metric')
                    ->formatStateUsing(fn (string $state): string => AchievementForm::METRIC_TYPES[$state] ?? $state)
                    ->badge(),

                TextColumn::make('threshold')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('target_id')
                    ->label('Target')
                    ->placeholder('—'),

                TextColumn::make('users_count')
                    ->label('Earned By')
                    ->counts('users')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
