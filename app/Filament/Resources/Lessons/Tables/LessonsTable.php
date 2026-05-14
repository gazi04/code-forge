<?php

namespace App\Filament\Resources\Lessons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->is_boss ? '🏆 Boss Level' : null),

                TextColumn::make('course.name')
                    ->label('Course')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('xp_reward')
                    ->label('XP')
                    ->icon('heroicon-m-sparkles')
                    ->iconColor('info')
                    ->alignCenter(),

                TextColumn::make('coin_reward')
                    ->label('Coins')
                    ->icon('heroicon-m-circle-stack')
                    ->iconColor('warning')
                    ->alignCenter(),

                IconColumn::make('is_boss')
                    ->label('Boss')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-bolt'),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->relationship('course', 'name')
                    ->label('Filter by Course')
                    ->searchable()
                    ->preload(),
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
