<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\Achievement;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachedAchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievements';

    protected static ?string $title = 'Achievements';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('pivot_unlocked_at', 'desc')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Art')
                    ->square()
                    ->size(40),

                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('metric_type')
                    ->label('Metric')
                    ->badge(),

                TextColumn::make('pivot.unlocked_at')
                    ->label('Unlocked At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('grant')
                    ->label('Grant Achievement')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('achievement_id')
                            ->label('Achievement')
                            ->options(fn (): array => Achievement::all()->pluck('name', 'id')->toArray())
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data): void {
                        $owner = $this->getOwnerRecord();
                        $achievement = Achievement::find($data['achievement_id']);

                        $owner->achievements()->syncWithoutDetaching([
                            $data['achievement_id'] => ['unlocked_at' => now()],
                        ]);

                        activity()
                            ->performedOn($owner)
                            ->causedBy(auth()->user())
                            ->event('admin.grant_achievement')
                            ->log("Admin manually granted the \"{$achievement->name}\" achievement.");
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $owner = $this->getOwnerRecord();

                        $owner->achievements()->detach($record->id);

                        activity()
                            ->performedOn($owner)
                            ->causedBy(auth()->user())
                            ->event('admin.revoke_achievement')
                            ->log("Admin manually revoked the \"{$record->name}\" achievement.");
                    }),
            ])
            ->groupedBulkActions([]);
    }
}
