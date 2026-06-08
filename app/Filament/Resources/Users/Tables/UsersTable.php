<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Redis;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'student' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('level')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('xp')
                    ->label('XP')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('coins')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_shadowbanned')
                    ->label('Banned')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrators',
                        'student' => 'Students',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                Action::make('toggleShadowban')
                    ->label(fn ($record): string => $record->is_shadowbanned ? 'Unban' : 'Shadowban')
                    ->icon(fn ($record): string => $record->is_shadowbanned ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn ($record): string => $record->is_shadowbanned ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record): string => $record->is_shadowbanned ? 'Unban student?' : 'Shadowban student?')
                    ->modalDescription(fn ($record): string => $record->is_shadowbanned
                        ? "Restore {$record->name} to the public leaderboard."
                        : "Hide {$record->name} from the public leaderboard and remove them from all Redis sets. They will still be able to use the platform."
                    )
                    ->action(function ($record): void {
                        $record->is_shadowbanned = ! $record->is_shadowbanned;
                        $record->save();

                        if ($record->is_shadowbanned) {
                            Redis::zrem('leaderboard:all_time', $record->name);
                            Redis::zrem('leaderboard:weekly', $record->name);
                        } else {
                            Redis::zadd('leaderboard:all_time', $record->xp, $record->name);
                        }
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
