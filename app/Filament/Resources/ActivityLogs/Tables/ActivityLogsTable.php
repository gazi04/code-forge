<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'deleted' => 'danger',
                        'updated' => 'warning',
                        'admin.reset' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('system')
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('Target Resource')
                    ->formatStateUsing(fn ($state) => class_basename($state) ?: 'System')
                    ->searchable(),

                TextColumn::make('subject_id')
                    ->label('Target ID')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('description')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('causer.name')
                    ->label('Triggered By')
                    ->placeholder('System Event')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'admin.reset' => 'Admin Reset',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Inspect')
                    ->modalHeading('Audit Log Detail')
                    ->modalWidth('3xl')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
